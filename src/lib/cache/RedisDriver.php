<?php

/**
 * Redis缓存驱动
 */

namespace dux\lib\cache;

class RedisDriver implements CacheInterface {

    protected $driver = NULL;
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'group' => 0,
    );

    public function __construct($config = array()) {
        $this->config = array_merge($this->config, (array)$config);
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

    public function set($key, $value, $expire = 1800) {
        if($expire) {
            return $this->driver->setex($key, $expire, $value);
        }else {
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