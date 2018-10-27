<?php

/**
 * Redis存储驱动
 */

namespace dux\lib\storage;

class RedisDriver implements StorageInterface {

    protected $driver = NULL;
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'group' => 0,
    );

    public function __construct($config = array(), $group = 0) {
        $this->config = array_merge($this->config, (array)$config);
        $this->config['group'] = intval($group);
        $this->driver = new \Redis();
        $this->driver->connect($this->config['host'], $this->config['port']);
        if($this->config['password']) {
            $this->driver->auth($this->config['password']);
        }
        $this->driver->select($this->config['group']);
    }

    public function get($key) {
        return $this->driver->get($key);
    }

    public function set($key, $value, $expire = 0) {
        if($expire) {
            return $this->driver->setex($key, $expire, $value);
        }else{
            return $this->driver->set($key, $value);
        }
    }

    public function inc($key, $value = 1) {
        return $this->driver->incrBy($key, $value);
    }

    public function des($key, $value = 1) {
        return $this->driver->decrBy($key, $value);
    }

    public function del($key) {
        return $this->driver->delete($key);
    }

    public function clear() {
        return $this->driver->flushDB();
    }
}