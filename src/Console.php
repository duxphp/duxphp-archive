<?php

namespace dux;

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

class Console {

    /**
     * 默认模块
     * @var array
     */
    public static $module = [
        'help' => \dux\console\HelpDriver::class,
        'ide' => \dux\console\IdeDriver::class
    ];

    /**
     * 执行命令
     */
    public static function run() {
        $model = $_SERVER["argv"][1];
        $param = $_SERVER["argv"][2];
        if (empty($model)) {
            $model = 'help';
        }
        $modelArray = explode(':', $model, 2);
        $name = $modelArray[0];
        $method = $modelArray[1] ?: 'default';

        if (!self::$module[$name]) {
            exit('Module does not exist');
        }
        if (!class_exists(self::$module[$name])) {
            exit('The module class does not exist');
        }
        $class = new self::$module[$name]();
        if (!$class instanceof \dux\console\ConsoleInterface) {
            exit('The console class must interface class inheritance');
        }
        if(!method_exists($class, $method)) {
            exit('Module approach does not exist');
        }
        exit(call_user_func([$class, $method], $param));
    }

    /**
     * 注册命令
     * @param $model
     * @param $class
     */
    public static function register($model, $class) {
        if (self::$module[$model]) {
            exit('Registration module has been in existence');
        }
        self::$module[$model] = $class;
    }

}