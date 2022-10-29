<?php

namespace dux;

/**
 * 注册框架方法
 */
class Dux {

    /**
     * 注入对象
     * @var null
     */
    private static $di = null;

    /**
     * 依赖注入
     * @return com\Di|null
     */
    public static function di(): ?\dux\com\Di {
        if (!isset(self::$di)) {
            self::$di = new \dux\com\Di();
        }
        return self::$di;
    }

    /**
     * 获取路由对象
     * @return object
     */
    public static function route(): ?\dux\com\Rotue {
        $key = 'dux.route';
        if (!self::di()->has($key)) {
            self::di()->set($key, function () {
                return new \dux\com\Rotue();
            });
        }
        return self::di()->get($key);
    }

    /**
     * 模型类
     * @param array $config
     * @return object
     */
    public static function model(array $config = []): ?\dux\kernel\Model {
        $config = $config ?: \dux\Config::get('dux.database');
        $key = 'dux.database.' . http_build_query($config);
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($config) {
                $type = $config['type'];
                unset($config['type']);
                return new \dux\kernel\Model($type, $config);
            });
        }
        return self::di()->get($key);
    }

    /**
     * 模板引擎类
     * @param array $config
     * @return kernel\View|null
     */
    public static function view(array $config = []): ?\dux\kernel\View {
        $config = $config ?: \dux\Config::get('dux.tpl');
        $key = 'dux.tpl' . http_build_query($config);
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($config) {
                return new \dux\kernel\View($config);
            });
        }
        return self::di()->get($key);
    }

    /**
     * 注册缓存类
     * @param string $group
     * @param array $config
     * @return com\Cache|null
     */
    public static function cache(string $group = 'default', array $config = []): ?\dux\com\Cache {
        $config = $config ?: \dux\Config::get('dux.cache');
        $key = 'dux.cache.' . http_build_query($config);
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($config, $group) {
                $type = $config['type'];
                unset($config['type']);
                return new \dux\com\Cache($type, $config, $group);
            });
        }
        return self::di()->get($key);
    }

    /**
     * 注册会话类
     * @param string $pre
     * @param array $config
     * @return object
     */
    public static function session(string $pre = '', array $config = []): ?\dux\lib\Session {
        $config = $config ?: \dux\Config::get('dux.session');
        $key = 'dux.session.' . $pre . http_build_query($config);
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($config, $pre) {
                return new \dux\lib\Session($config, $pre);
            });
        }
        return self::di()->get($key);
    }

    /**
     * 获取请求数据
     * @param string $method
     * @param string $key
     * @param null $default
     * @param null $function
     * @return array|false|mixed|string
     */
    public static function request(string $method = '', string $key = '', $default = null, $function = null) {
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
        foreach ($data as $k => &$vo) {
            if ($function) {
                $vo = call_user_func($function, $vo);
            }
            if (!is_null($default) && empty($vo)) {
                $vo = $default;
            }
            if (is_string($vo)) {
                $vo = trim($vo);
                switch ($vo) {
                    case 'null':
                    case 'undefined':
                        $vo = null;
                    case 'true':
                        $vo = true;
                    case 'false':
                        $vo = false;
                    default:
                        $vo = html_in((string)$vo);
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
     * URL生成
     * @param string $str
     * @param array $params
     * @param bool $domain
     * @param bool $ssl
     * @return string
     */
    public static function url(string $str = '', array $params = [], bool $domain = false, bool $ssl = true) {
        $urlParams = explode(' ', $str, 2);
        $urlParams = array_map(function ($vo) {
            return trim($vo);
        }, $urlParams);
        if (!in_array(strtoupper($urlParams[0]), self::route()->method())) {
            $urlParams = ['ALL', $urlParams[0]];
        }
        $pathUrl = self::route()->get($urlParams[0], $urlParams[1], $params);
        if ($domain) {
            return ($ssl ? DOMAIN : DOMAIN_HTTP) . $pathUrl;
        } else {
            return $pathUrl;
        }
    }

    /**
     * 模块调用
     * @param string $class
     * @param string $layer
     * @return object
     * @throws \Exception
     */
    public static function target(string $class, string $layer = 'model') {
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
            self::di()->set($class, $class);
        }
        return self::di()->get($class);
    }

    /**
     * 加载配置文件
     * @param string $file
     * @param bool $enforce
     * @return mixed
     * @throws \Exception
     */
    public static function loadConfig(string $file, bool $enforce = true) {
        $file = ROOT_PATH . $file . '.php';
        try {
            $data = \dux\Config::load($file);
        } catch (\Exception $e) {
            if ($enforce) {
                throw new \Exception($e->getMessage());
            }
        }
        return $data;
    }

    /**
     * 保存配置到文件
     * @param string $file
     * @param array $config
     * @return bool
     * @throws \Exception
     */
    public static function saveConfig(string $file, array $config) {
        $file = ROOT_PATH . $file . '.php';
        return \dux\Config::save($file, $config);
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
     * @param int $code
     * @param callable|null $callback
     * @param array $hander
     * @param string $message
     */
    public static function header(int $code, ?callable $callback = null, array $hander = []) {
        if (!IS_CLI) {
            $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
            if (!isset(self::$codes[$code])) {
                $code = 500;
            }
            if (!headers_sent()) {
                header(implode(' ', [$protocol, $code, self::$codes[$code]]));
                foreach ($hander as $key => $vo) {
                    header($key . ': ' . $vo);
                }
            }
        }
        if ($callback) {
            echo call_user_func($callback);
        }
    }

    /**
     * 页面不存在
     */
    public static function notFound() {
        self::errorPage('404 Not Found', 404);
    }

    /**
     * 错误页面
     * @param string $title
     * @param int $code
     */
    public static function errorPage(string $title, int $code = 503) {
        if (!IS_CLI) {
            throw new \dux\exception\Message($title, $code);
        } else {
            exit($title);
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
     * @return mixed
     */
    public static function log($msg, string $type = 'INFO', string $fileName = '') {
        return self::logObj()->set($msg, $type, $fileName);
    }

    /**
     * 日志对象
     * @return object
     */
    public static function logObj(): ?\dux\com\Log {
        $config = \dux\Config::get('dux.log');
        $keyName = 'dux.log.' . http_build_query($config);
        if (!self::di()->has($keyName)) {
            $type = $config['type'];
            unset($config['type']);
            self::di()->set($keyName, function () use ($type, $config) {
                return new \dux\com\Log($type, $config);
            });
        }
        return self::di()->get($keyName);
    }

    /**
     * 设置Cookie
     * @param $key
     * @param $value
     * @param int $expire
     */
    public static function setCookie($key, $value, $expire = 3600) {
        if (!is_array($value)) {
            $value = [$value];
        }
        $value = json_encode($value);
        setcookie(\dux\Config::get('dux.use.pre') . $key, $value, time() + $expire, ROOT_URL . "/");
    }


    /**
     * 获取cookie
     * @param $key
     * @return mixed
     */
    public static function getCookie($key) {
        $data = $_COOKIE[\dux\Config::get('dux.use.pre') . $key];
        $data = json_decode($data, true);
        if ($data && count($data) == 1 && isset($data[0])) {
            return $data[0];
        }
        return $data;
    }

    /**
     * 删除Cookie
     * @param $key
     * @param $value
     * @param int $expire
     */
    public static function delCookie($key) {
        setcookie(\dux\Config::get('dux.use.pre') . $key, null);
    }

    /**
     * JWT编码
     * @param $data
     * @param int $exp
     * @param string $iss
     * @param string $aud
     * @return string
     */
    public static function jwtEncode($data, int $exp = 3600, ?string $iss = null, ?string $aud = null) {
        $time = time();
        $token = [
            "iss" => $iss ?: DOMAIN,
            "aud" => $aud ?: DOMAIN,
            "iat" => $time,
            "nbf" => $time,
            'data' => $data
        ];
        if ($exp) {
            $token['exp'] = $time + $exp;
        }
        $key = \dux\Config::get('dux.use.safe_key');
        return \Firebase\JWT\JWT::encode($token, $key);
    }

    /**
     * JWT解码
     * @param string $jwt
     * @return object
     */
    public static function jwtDecode(string $jwt) {
        $key = \dux\Config::get('dux.use.safe_key');
        return (array)\Firebase\JWT\JWT::decode($jwt, $key, ['HS256']);
    }

    /**
     * redis类
     * @param array $config
     * @param int|null $database
     * @return \Redis|null
     */
    public static function redis(array $config = [],?int $database = null) : ?\Redis {
        $config = $config ?: \dux\Config::get('dux.redis');
        if(!is_null($database)){
            $config['database'] = $database;
        }else if(empty($config['database'])){
            $config['database'] = 0;
        }
        $key = 'dux.redis.' . 'dbIndex_'. $config['database'] .'_.' . http_build_query($config);
        if (!self::di()->has($key)) {
            self::di()->set($key, function () use ($config) {
                return (new \dux\lib\Redis($config))->link();
            });
        }
        return self::di()->get($key);
    }

    /**
     * 委托
     * @param string $key
     * @return lib\Delegate|null
     */
    public static function delegate(string $key): ?\dux\lib\Delegate {
        $keyName = 'dux.delegate.' . $key;
        if (!self::di()->has($keyName)) {
            self::di()->set($keyName, function (){
                return new \dux\lib\Delegate();
            });
        }
        return self::di()->get($keyName);
    }

    /**
     * 语言翻译
     * @param string $str
     * @param string $lang
     * @return string
     */
    public static function lang(string $str,string $lang = 'en_us'): string {
        $keyName = 'dux.lang';
        if (!self::di()->has($keyName)) {
            self::di()->set($keyName, function () use($lang){
                return new \dux\lib\Lang($lang);
            });
        }
        $obj = self::di()->get($keyName);
        return $obj->lang($str,$lang);
    }

    /**
     * 设置语言
     * @param $lang
     * @return void
     */
    public static function setLang($lang)
    {
        $expire = 99 * 365 * 24 * 3600;
        if(empty($lang)){
            $expire = -1;
        }
        \dux\Dux::setCookie(LAYER_NAME . '_lang',$lang, $expire);
    }

}
