<?php

namespace dux\lib;

/**
 * 存储类
 *
 * @author Mr.L <349865361@qq.com>
 */

class Storage {

    /**
     * 配置
     * @var array
     */
    protected $config = array();

    /**
     * 驱动配置
     */
    protected $storage = 'default';

    /**
     * 驱动对象
     * @var array
     */
    protected static $objArr = array();

    /**
     * 组ID
     * @var int
     */
    protected $group = 0;

    /**
     * 实例化类
     * @param string $storage
     * @param int $group
     * @throws \Exception
     */
    public function __construct($storage = 'default', $group = 0) {
        if( $storage ){
            $this->storage = $storage;
        }
        $config = \Config::get('dux.storage');
        $this->config = $config[$this->storage];
        $this->group = $group;
        if( empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->storage.' storage config error', 500);
        }
    }

    /**
     * 回调驱动
     * @param $method
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args) {
        if( !isset(self::$objArr[$this->storage]) ){
            $driver = __NAMESPACE__ . '\storage\\' . ucfirst($this->config['type']) . 'Driver';
            if (!class_exists($driver)) {
                throw new \Exception("Cache Driver '{$driver}' not found'", 500);
            }
            self::$objArr[$this->storage] = new $driver($this->config, $this->group);
        }
        return call_user_func_array(array(self::$objArr[$this->storage], $method), $args);
    }

}