<?php

namespace dux;

use dux\exception\Exception;

class Engine {

    public static $classes = [];

    /**
     * Engine constructor.
     */
    public function __construct() {
        $this->init();
    }

    /**
     * 初始化框架
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
        spl_autoload_register([__CLASS__, 'loadClass']);
    }

    /**
     * 自动加载类文件
     * @param string $class
     * @return bool
     */
    public function loadClass(string $class) {
        $classFile = str_replace(['\\', '_'], '/', $class) . '.php';
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
     * 异常接管
     */
    public function handleErrors() {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * 错误接管
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     */
    public function handleError($errno, $errstr, $errfile, $errline) {
        if ($errno & error_reporting()) {
            $handle = new \dux\exception\Handle($errstr, $errno, $errfile, $errline, [], \dux\Config::get('dux.debug'), \dux\Config::get('dux.debug_error'), \dux\Config::get('dux.debug_log'));
            $handle->render();
            exit;
        }
    }

    /**
     * 异常接管
     * @param $e
     */
    public function handleException($e) {
        if ($e instanceof \dux\exception\Message) {
            $handle = new \dux\exception\Handle($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), false, true, false, true);
        } else {
            $handle = new \dux\exception\Handle($e->getMessage(), $e->getCode(), $e->getFile(), $e->getLine(), $e->getTrace(), \dux\Config::get('dux.debug'), \dux\Config::get('dux.debug_error'), \dux\Config::get('dux.debug_log'));
        }
        $handle->render();
    }

    /**
     * 解析路由
     */
    public function route() {
        $routes = \dux\Config::get('dux.route');
        foreach ($routes as $module => $rule) {
            if (empty($module)) {
                continue;
            }
            $rule = explode(" ", $rule, 2);
            array_map(function ($vo) {
                return trim($vo);
            }, $rule);
            [$method, $url] = $rule;
            if (empty($method) || empty($url)) {
                continue;
            }
            \dux\Dux::route()->add($method, $url, $module);
        }
        if (IS_CLI) {
            $params = getopt('u:q:');
            $url = $params['u'];
            $query = $params['q'];
            if ($query) {
                if (!is_array($query)) {
                    $query = [$query];
                }
                foreach ($query as $vo) {
                    $item = explode('=', $vo, 2);
                    $_GET[$item[0]] = $item[1];
                }
            }
        } else {
            $url = URL;
        }
        $data = \dux\Dux::route()->dispatch(METHOD ?: 'GET', $url ?: '');

        if (!defined('DEFAULT_LAYER_NAME')) {
            define('DEFAULT_LAYER_NAME', $data['default_layer']);
        }
        if (!defined('ROLE_NAME')) {
            define('ROLE_NAME', $data['role']);
        }
        if (!defined('LAYER_NAME')) {
            define('LAYER_NAME', $data['layer']);
        }
        if (!defined('APP_NAME')) {
            define('APP_NAME', strtolower($data['app']));
        }
        if (!defined('MODULE_NAME')) {
            define('MODULE_NAME', ucfirst($data['module']));
        }
        if (!defined('ACTION_NAME')) {
            define('ACTION_NAME', $data['action']);
        }
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
        if (!method_exists($class, $action) && !method_exists($class, '__call')) {
            \dux\Dux::notFound();
        }
        (new $class())->$action();
    }
}
