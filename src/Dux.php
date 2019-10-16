<?php

namespace dux;
/**
 * 注册框架方法
 */
class Dux {

    private static $fileList = [];
    private static $di = null;

    /**
     * 依赖注入
     * @return com\Di|null
     */
    public static function di() {
        if (!isset(self::$di)) {
            self::$di = new \dux\com\Di();
        }
        return self::$di;
    }

    /**
     * 注册模板引擎类
     * @param array $config
     * @return \dux\kernel\View
     */
    public static function view($config = []) {
        $sysConfig = \dux\Config::get('dux.tpl');
        $config = array_merge((array)$sysConfig, (array)$config);
        return new \dux\kernel\View($config);
    }

    /**
     * 注册缓存类
     * @param string $configName
     * @param int $group
     * @return lib\Cache
     * @throws \Exception
     */
    public static function cache($configName = 'default', $group = 0) {
        return new \dux\lib\Cache($configName, $group);
    }

    /**
     * 注册存储类
     * @param string $configName
     * @param int $group
     * @return \dux\lib\Storage
     */
    public static function storage($configName = 'default', $group = 0) {
        return new \dux\lib\Storage($configName, $group);
    }

    /**
     * 注册COOKIE类
     * @param string $configName
     * @return mixed
     */
    public static function cookie($configName = 'default') {
        $key = 'cookie.' . $configName;
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($configName) {
                return new \dux\lib\Cookie($configName);
            }, true);
        }
        return self::di()->get($key);
    }

    /**
     * 注册会话类
     * @param string $configName
     * @return mixed
     */
    public static function session($configName = 'default') {
        $key = 'session.' . $configName;
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($configName) {
                return new \dux\lib\Session($configName);
            }, true);
        }
        return self::di()->get($key);
    }

    /**
     * 获取请求数据
     * @param string $method
     * @param string $key
     * @param string $default
     * @param string $function
     * @return array|mixed|string
     */
    public static function request($method = '', $key = '', $default = '', $function = '') {
        $method = strtolower($method);
        switch ($method) {
            case 'get':
                $data = $_GET;
                break;
            case 'post':
                $data = $_POST;
                break;
            case 'input':
                $data = file_get_contents('php://input');
                if ($data) {
                    $data = json_decode($data, true);
                    $data = $data ? $data : [];
                } else {
                    $data = [];
                }
                break;
            default:
                $input = file_get_contents('php://input');
                $input = json_decode($input, true);
                $input = $input ? $input : [];
                $data = array_merge($_GET, $_POST, $input);
        }
        if ($key) {
            $data = [$data[$key]];
        }
        foreach ($data as $key => $vo) {
            if ($function) {
                $vo = call_user_func($function, $vo);
            }
            if (!empty($default) && empty($vo)) {
                $vo = $default;
            }
            if (is_string($vo)) {
                $vo = trim($vo);
                if ($vo == 'null' || $vo == 'undefined') {
                    $data[$key] = null;
                }
                if ($vo == 'true') {
                    $data[$key] = true;
                }
                if ($vo == 'false') {
                    $data[$key] = false;
                }
            }
        }
        if ($key) {
            return reset($data);
        } else {
            return $data;
        }
    }

    /**
     * URL生成方法
     * @param string $str
     * @param array $params
     * @param bool $domain
     * @param bool $ssl
     * @param bool $get
     * @return string
     */
    public static function url($str = '', $params = [], $domain = false, $ssl = true, $get = true) {
        $str = trim($str);
        $str = trim($str, '/');
        $str = trim($str, '\\');
        $param = explode('/', $str, 4);
        $param = array_filter($param);
        $paramCount = count($param);
        $module = \dux\Config::get('dux.module');
        switch ($paramCount) {
            case 1:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = MODULE_NAME;
                $action = lcfirst($param[0]);
                break;
            case 2:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = ucfirst($param[0]);
                $action = lcfirst($param[1]);
                break;
            case 3:
                $layer = LAYER_NAME;
                $app = strtolower($param[0]);
                $controller = ucfirst($param[1]);
                $action = lcfirst($param[2]);
                break;
            case 4:
                if ($param[0] == 'default') {
                    $layer = \dux\Config::get('dux.module_default');
                } else {
                    $layer = $param[0];
                }
                $app = strtolower($param[1]);
                $controller = ucfirst($param[2]);
                $action = lcfirst($param[3]);
                break;
            case 0:
            default:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = MODULE_NAME;
                $action = ACTION_NAME;
                break;
        }

        $longUrl = $module[$layer] . '/' . $app . '/' . $controller . '/' . $action;
        if ($layer <> \dux\Config::get('dux.module_default')) {
            $url = $longUrl;
        } else {
            $url = $app . '/' . $controller . '/' . $action;
        }

        $routeStr = '';
        $routes = \dux\Config::get('dux.routes');
        foreach ($routes as $key => $vo) {
            if ($longUrl == $vo) {
                $routeStr = $key;
                break;
            }
        }

        $route = \dux\Config::get('dux.route');
        $routeParams = explode(',', $route['params']);
        if ($_GET['webapp']) {
            $params['webapp'] = 1;
        }
        if (!empty($routeParams) && $get) {
            foreach ($routeParams as $vo) {
                if (isset($_GET[$vo]) && !isset($params[$vo])) {
                    $params[$vo] = $_GET[$vo];
                }
            }
        }
        if (empty($strParams)) {
            $fullUrl = ROOT_URL . '/' . $url;
        } else {
            $fullUrl = ROOT_URL . '/' . $url . '?' . http_build_query($strParams);
        }
        if ($domain) {
            return ($ssl ? DOMAIN : DOMAIN_HTTP) . $fullUrl;
        } else {
            return $fullUrl;
        }
    }

    /**
     * 类调用方法
     * @param $class
     * @param string $layer
     * @return mixed|object
     * @throws object
     */
    public static function target($class, $layer = 'model') {
        $param = explode('/', $class, 2);
        $paramCount = count($param);
        $app = '';
        $module = '';
        switch ($paramCount) {
            case 1:
                $app = APP_NAME;
                $module = $param[0];
                break;
            case 2:
                $app = $param[0];
                $module = $param[1];
                break;
        }
        $app = strtolower($app);
        $module = ucfirst($module);
        $class = "\\app\\{$app}\\{$layer}\\{$module}" . ucfirst($layer);
        if (!class_exists($class)) {
            throw new \Exception("Class '{$class}' not found", 500);
        }
        if (!self::di()->has($class)) {
            self::di()->set($class, $class, true);
        }
        return self::di()->get($class);
    }

    /**
     * 加载配置文件
     * @param $file
     * @param bool $enforce
     * @return array|mixed
     * @throws Exception
     */
    public static function loadConfig($file, $enforce = true) {
        $file = ROOT_PATH . $file . '.php';
        if (!is_file($file)) {
            if ($enforce) {
                throw new \Exception("File '{$file}' not found", 500);
            }
            return [];
        }
        return require($file);
    }

    /**
     * 保存配置
     * @param $file
     * @param $config
     * @return array|bool
     */
    public static function saveConfig($file, $config) {
        if (empty($config) || !is_array($config)) {
            return [];
        }
        $conf = load_config($file);
        $config = array_merge($conf, $config);
        $confString = var_export($config, true);
        $find = ["'true'", "'false'", "'1'", "'0'"];
        $replace = ["true", "false", "1", "0"];
        $confString = str_replace($find, $replace, $confString);
        $confString = "<?php \n return " . $confString . ';';
        if (file_put_contents(ROOT_PATH . $file . '.php', $confString)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 状态码
     * @var array
     */
    public static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',

        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',

        226 => 'IM Used',

        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',

        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',

        426 => 'Upgrade Required',

        428 => 'Precondition Required',
        429 => 'Too Many Requests',

        431 => 'Request Header Fields Too Large',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',

        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * 发送HTTP头
     * @param $code
     * @param callable $callback
     */
    public static function header($code, callable $callback = null) {
        if (!headers_sent()) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            header(implode(' ', [$protocol, $code, self::$codes[$code]]));
        }
        exit($callback());
    }

    /**
     * 页面不存在
     */
    public static function notFound() {
        if (!IS_CLI) {
            static::header(404, function () {
                if (!headers_sent()) {
                    header("Content-Type: text/html; charset=UTF-8");
                }
                echo file_get_contents(CORE_PATH . 'tpl/404.html');
            });
        } else {
            echo 'The request does not exist';
            return;
        }
    }

    /**
     * 错误页面
     * @param $title
     * @param $msg
     * @param int $code
     */
    public static function errorPage($title, $msg, $code = 503) {
        if (!IS_CLI) {
            static::header($code, function () use ($title, $msg, $code) {
                if (!headers_sent()) {
                    header("Content-Type: text/html; charset=UTF-8");
                }
                $html = file_get_contents(CORE_PATH . 'tpl/error.html');
                $html = str_replace('{$title}', $title, $html);
                $html = str_replace('{$code}', $code, $html);
                $html = str_replace('{$msg}', $msg, $html);
                exit($html);
            });
        } else {
            echo $msg;
            return;
        }
    }

    /**
     * 运行时间
     * @return string
     */
    public static function runTime() {
        if (!defined("START_TIME")) {
            return "";
        }
        $stime = explode(" ", START_TIME);
        $etime = explode(" ", microtime());
        return sprintf("%0.4f", round($etime[0] + $etime[1] - $stime[0] - $stime[1], 4));
    }

    /**
     * 日志写入
     * @param $msg
     * @param string $type
     * @param string $fileName
     * @return bool
     * @throws \Exception
     */
    public static function log($msg, $type = 'INFO', $fileName = '') {
        $types = ['INFO', 'WARN', 'DEBUG', 'ERROR'];
        $type = strtoupper($type);
        if (!in_array($type, $types)) {
            $type = 'INFO';
        }
        $logDriver = defined('LOG_DRIVER') ? LOG_DRIVER : \dux\Config::get('dux.log_driver');
        if (empty($logDriver)) {
            $logDriver = 'files';
        }
        $keyName = 'log.' . $logDriver;
        $driver = null;

        if (!self::di()->has($keyName)) {
            self::di()->set($keyName, function () use ($logDriver) {
                return new \dux\lib\Log($logDriver);
            }, true);
        }
        $driver = self::di()->get($key);

        $flag = null;
        $trace = debug_backtrace();
        if ($trace[1]['file']) {
            $curTarce = $trace[1];
        } else {
            $curTarce = $trace[0];
        }
        $queryData = \dux\Engine::parserArray($_GET);
        $requestData = \dux\Engine::parserArray(request());
        $file = \dux\Engine::parserFile($curTarce['file']);
        try {
            $flag = $driver->log($msg, $type, $fileName);
        } catch (\Exception $e) {
            if (!defined('LOG_DRIVER')) define('LOG_DRIVER', 'files');
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $flag;
    }

}
