<?php

namespace dux;

class Engine {

    protected static $classes = array();

    public function __construct() {
        $this->init();
    }

    /**
     * 初始化
     */
    public function init() {
        $this->autoload();
        $this->handleErrors();
        $this->route();
    }

    /**
     * 注册类
     */
    public function autoload() {
        spl_autoload_register(array(__CLASS__, 'loadClass'));
    }

    /**
     * 加载类文件
     * @param $class
     * @return bool
     */
    public function loadClass($class) {
        $classFile = str_replace(array('\\', '_'), '/', $class) . '.php';
        $file = ROOT_PATH . $classFile;
        if (!isset(self::$classes[$file])) {
            if (!file_exists($file)) {
                return false;
            }
            self::$classes[$classFile] = $file;
            require_once $file;
        }
        return true;
    }

    /**
     * 结果异常错误
     */
    public function handleErrors() {
        set_error_handler(array($this, 'handleError'));
        set_exception_handler(array($this, 'handleException'));
    }

    /**
     * 错误接管
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @throws \ErrorException
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if ($errno & error_reporting()) {
            throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
    }

    /**
     * 异常接管
     * @param $e
     */
    public function handleException($e) {
        $msg = "{$e->getMessage()} line {$e->getLine()} in file {$this->parserFile($e->getFile())}";
        if (\dux\Config::get('dux.log')) {
            \dux\Dux::log($msg);
        }
        if (IS_CLI) {
            echo 'error: ' . $msg;
        } else if (isAjax()) {
            if (!\dux\Config::get('dux.debug')) {
                $msg = \dux\Dux::$codes[500];
            }
            $data = [
                'code' => 500,
                'message' => $msg,
                'line' => "line {$e->getLine()} in file {$this->parserFile($e->getFile())}",
                'trace' => $e->getTrace(),
            ];
            \dux\Dux::header(500, function () use ($data) {
                if (!headers_sent()) {
                    header("application/json; charset=UTF-8");
                }
                echo json_encode($data);
            });
        } else {
            $html = "<title>Dux System Engine</title>";
            if (!\dux\Config::get('dux.debug')) {
                \dux\Dux::notFound();
            } else {
                $html .= "<h1>{$e->getMessage()}</h1>";
                $html .= "<code>line {$e->getLine()} in file {$this->parserFile($e->getFile())}</code>";
                $html .= "<p>";
                foreach ($e->getTrace() as $value) {
                    $html .= "{$value['file']} line {$value['line']} <br>";
                }
                $html .= "</p>";
            }

            $html .= "<p> run time " . \dux\Dux::runTime() . "s</p>";

            \dux\Dux::header(500, function () use ($html) {
                if (!headers_sent()) {
                    header("Content-Type: text/html; charset=UTF-8");
                }
                echo $html;
            });
        }
        exit;
    }

    private function parserFile($file) {
        return str_replace(ROOT_PATH, '/', $file);
    }

    /**
     * 解析路由
     */
    public function route() {
        if (!IS_CLI) {
            $url = $_SERVER['REQUEST_URI'];
            if (ROOT_URL) {
                $strPos = strpos($_SERVER['REQUEST_URI'], ROOT_URL);
                if ($strPos === 0) {
                    $url = substr($_SERVER['REQUEST_URI'], strlen(ROOT_URL));
                }
            }
        } else {
            $params = getopt('u:');
            $url = $params['u'];
        }

        $routes = \dux\Config::get('dux.routes');
        foreach ($routes as $rule => $mapper) {
            $rule = '/' . str_ireplace(array('\\\\', 'http://', '-', '/', '<', '>', '.'), array('', '', '\-', '\/', '(?<', ">[a-z0-9_%]+)", '\.'), $rule) . '/i';
            if (preg_match($rule, $url, $matches, PREG_OFFSET_CAPTURE)) {
                if ($matches[0][1] !== 0) {
                    continue;
                }
                foreach ($matches as $matchkey => $matchval) {

                    if (('layer' === $matchkey)) {
                        $mapper = str_ireplace('<layer>', $matchval[0], $mapper);
                    } else if ('app' === $matchkey) {
                        $mapper = str_ireplace('<app>', $matchval[0], $mapper);
                    } else if ('module' === $matchkey) {
                        $mapper = str_ireplace('<module>', $matchval[0], $mapper);
                    } else if ('action' === $matchkey) {
                        $mapper = str_ireplace('<action>', $matchval[0], $mapper);
                    } else {
                        if (!is_int($matchkey)) {
                            $_GET[$matchkey] = $matchval[0];
                        }

                    }
                }
                $url = $mapper;
                break;
            }
        }

        $url = trim($url, '/');
        $urlParse = parse_url($url);
        $urlPath = explode('.', $urlParse['path'], 2);
        $urlArray = explode("/", $urlPath[0], 5);
        $moduleConfig = \dux\Config::get('dux.module');
        $moduleRule = array_flip($moduleConfig);
        $roleName = null;
        unset($_GET['/' . $urlParse['path']]);
        if (in_array($urlArray[0], $moduleConfig)) {
            $roleName = $urlArray[0];
            $layer = $moduleRule[$urlArray[0]];
            $appName = $urlArray[1];
            $modelName = $urlArray[2];
            $actionName = $urlArray[3];
            $params = $urlArray[4];
        } else {
            foreach ($moduleRule as $key => $vo) {
                if (empty($key)) {
                    $layer = $vo;
                    continue;
                }
            }
            $appName = $urlArray[0];
            $modelName = $urlArray[1];
            $actionName = $urlArray[2];
            $params = $urlArray[3] . '/' . $urlArray[4];
        }
        $layer = empty($layer) ? \dux\Config::get('dux.module_default') : $layer;
        $appName = empty($appName) ? 'index' : $appName;
        $modelName = empty($modelName) ? 'Index' : $modelName;
        $actionName = empty($actionName) ? 'index' : $actionName;
        if (!defined('VIEW_LAYER_NAME')) {
            if ($layer != 'mobile' && $layer != 'controller') {
                define('VIEW_LAYER_NAME', \dux\Config::get('dux.module_default'));
            } else {
                define('VIEW_LAYER_NAME', $layer);
            }
        }
        if (!defined('ROLE_NAME')) {
            define('ROLE_NAME', $roleName);
        }

        if (!defined('LAYER_NAME')) {
            define('LAYER_NAME', $layer);
        }

        if (!defined('APP_NAME')) {
            define('APP_NAME', strtolower($appName));
        }

        if (!defined('MODULE_NAME')) {
            define('MODULE_NAME', ucfirst($modelName));
        }

        if (!defined('ACTION_NAME')) {
            define('ACTION_NAME', $actionName);
        }

        $paramArray = explode("/", $params);
        if (!empty($paramArray)) {
            $paramArray = array_filter($paramArray);
        }

        $get = [];
        foreach ($paramArray as $key => $value) {
            $list = explode('-', $value, 2);
            if (count($list) == 2) {
                $get[$list[0]] = $list[1];
            }
        }
        $_GET = array_merge($get, $_GET);
    }

    /**
     * 运行框架
     * @throws \Exception
     */
    public function run() {
        $class = '\app\\' . APP_NAME . '\\' . LAYER_NAME . '\\' . MODULE_NAME . ucfirst(LAYER_NAME);
        $action = ACTION_NAME;
        if (!class_exists($class)) {
            \dux\Dux::notFound();
        }
        $obj = new $class();
        if (!method_exists($class, $action) && !method_exists($class, '__call')) {
            \dux\Dux::notFound();
        }
        $obj->$action();
    }
}
