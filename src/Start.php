<?php

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

/**
 * 定义内核版本
 */
const  VERSION = '1.0.0';
const  VERSION_DATE = '20170209';

/**
 * 最低PHP版本要求
 */
const PHP_REQUIRED = '5.6.0';


class Start {

    private function __construct() {
    }

    private function __destruct() {
    }

    private function __clone() {
    }

    public static $_routes = array();


    /**
     * 运行框架
     */
    public static function run() {
        self::environment();
        self::definitions();
        self::loadFile();
        self::loadConfig();
        self::loadClass();
        self::registerCom();
        self::loadFunCom();
        self::route();
        self::start();
    }

    /**
     * 定义常量
     */
    protected static function definitions() {
        if (!defined('URL')) define('URL', $_SERVER['REQUEST_URI']);
        if (!defined('START_TIME')) define('START_TIME', microtime());
        if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
        if (!defined('ROOT_PATH')) define('ROOT_PATH', str_replace('\\', '/', dirname(__DIR__)) . '/');
        if (!defined('CORE_PATH')) define('CORE_PATH', ROOT_PATH . 'dux/');
        if (!defined('DATA_PATH')) define('DATA_PATH', ROOT_PATH . 'data/');
        if (!defined('APP_PATH')) define('APP_PATH', ROOT_PATH . 'app/');
        if (!defined('PACK_PATH')) define('PACK_PATH', CORE_PATH . 'package/');
        if (!defined('ROOT_URL')) define('ROOT_URL', str_replace('\\', '/', rtrim(dirname($_SERVER["SCRIPT_NAME"]), '\\/')));
        if (!defined('ROOT_SCRIPT')) define('ROOT_SCRIPT', str_replace('\\', '/', rtrim($_SERVER["SCRIPT_NAME"], '\\/')));
        $urlHead = ($_SERVER['HTTPS'] <> "on") ? 'http' : 'https';
        $urlHead .= '://' . $_SERVER["HTTP_HOST"];
        if (!defined('DOMAIN')) define('DOMAIN', $urlHead);
        $urlHead = 'http://' . $_SERVER["HTTP_HOST"];
        if (!defined('DOMAIN_HTTP')) define('DOMAIN_HTTP', $urlHead);
    }

    /**
     * 设置环境
     */
    protected static function environment() {
        //设置跨域
        header('Access-Control-Allow-Origin:'.$_SERVER["HTTP_ORIGIN"]);
        header('Access-Control-Allow-Headers:'.$_SERVER["HTTP_ACCESS_CONTROL_REQUEST_HEADERS"]);
        //判断PHP版本
        if (version_compare(PHP_VERSION, PHP_REQUIRED, '<')) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 500 PHP_VERSION');
            exit;
        }
        //兼容环境信息
        date_default_timezone_set('PRC');
        if (!isset($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = '';
        }
        if (!isset($_SERVER['SERVER_PROTOCOL'])
            || ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')
        ) {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = strtolower($_SERVER['HTTP_HOST']);
        } else {
            $_SERVER['HTTP_HOST'] = '';
        }
        if (!isset($_SERVER['REQUEST_URI'])) {
            if (isset($_SERVER['argv'])) {
                $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['argv'][0];
            } else {
                $uri = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
            }
            $_SERVER['REQUEST_URI'] = $uri;
        }
        if (!function_exists('getallheaders')) {
            function getallheaders() {
                foreach ($_SERVER as $name => $value) {
                    if (substr($name, 0, 5) == 'HTTP_') {
                        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                    }
                }
                return $headers;
            }
        }

    }

    /**
     * 加载核心文件
     */
    protected static function loadFile() {
        $loadFile = ROOT_PATH . 'vendor/autoload.php';
        if(file_exists($loadFile)) {
            require $loadFile;
        }
        require ROOT_PATH . 'dux/Dux.php';
        require ROOT_PATH . 'dux/Engine.php';
        require ROOT_PATH . 'dux/Config.php';
    }

    /**
     * 加载配置
     */
    protected static function loadConfig() {
        $config = require(DATA_PATH . 'config/global.php');
        Config::set($config);
    }

    /**
     * 加载核心类
     */
    protected static function loadClass() {

    }

    /**
     * 注册核心方法
     */
    protected static function registerCom() {
    }

    /**
     * 加载公共函数库
     */
    protected static function loadFunCom() {
        require CORE_PATH . 'kernel/Function.php';
    }

    /**
     * 注册路由
     */
    protected static function route() {
    }

    /**
     * 启动框架
     */
    protected static function start() {
        if (strtolower($_SERVER['REQUEST_METHOD']) == 'options') {
            Dux::header(204);
        }
        $dux = new \dux\Engine();
        $dux->run();
    }

}