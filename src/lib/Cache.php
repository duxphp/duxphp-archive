<?php

namespace dux\lib;

/**
 * 缓存类
 *
 * @author Mr.L <349865361@qq.com>
 */

class Cache {

    /**
     * 配置
     * @var array
     */
    protected $config = array();

    /**
     * 驱动配置
     */
    protected $cache = 'default';

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
     * @param string $cache
     * @param int $group
     * @throws \Exception
     */
    public function __construct($cache = 'default', $group = 0) {
        if( $cache ){
            $this->cache = $cache;
        }
        $config = \Config::get('dux.cache');
        $this->config = $config[$this->cache];
        $this->group = $group;
        if( empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->cache.' cache config error', 500);
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
        if( !isset(self::$objArr[$this->cache]) ){
            $driver = __NAMESPACE__ . '\cache\\' . ucfirst($this->config['type']) . 'Driver';
            if (!class_exists($driver)) {
                throw new \Exception("Cache Driver '{$driver}' not found'", 500);
            }
            self::$objArr[$this->cache] = new $driver($this->config, $this->group);
        }
        return call_user_func_array(array(self::$objArr[$this->cache], $method), $args);
    }

}