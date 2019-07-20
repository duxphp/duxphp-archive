<?php

namespace dux\lib;

/**
 * 日志类
 *
 * @author TS
 */

class Log {

    /**
     * 配置
     * @var array
     */
    protected $config = array();

    /**
     * 驱动配置
     */
    protected $log = 'files';

    /**
     * 驱动对象
     * @var array
     */
    protected static $objArr = array();

    /**
     * 实例化类
     * @param string $log
     */
    public function __construct($log = '') {
        if(!empty($log))
            $this->log = $log;
    }

    /**
     * 回调驱动
     * @param $method
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args) {
        $key = 'log.' . $this->log;
        if(!isset(self::$objArr[$key]) ){
            $driver = __NAMESPACE__ . '\log\\' . ucfirst($this->log) . 'Driver';
            if (!class_exists($driver)) {
                throw new \Exception("Log Driver '{$driver}' not found'", 500);
            }
            self::$objArr[$key] = new $driver();
        }
        return call_user_func_array(array(self::$objArr[$key], $method), $args);
    }

}