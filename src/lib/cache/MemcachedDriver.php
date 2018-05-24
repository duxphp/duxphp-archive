<?php

/**
 * Memcached缓存驱动
 */

namespace dux\lib\cache;

class MemcachedDriver implements CacheInterface {

    protected $driver = NULL;
    protected $group = NULL;
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 11211,
        'group' => 'tmp',
    );

    public function __construct($config = array()) {
        $this->config = array_merge($this->config, (array)$config);

        $this->driver = new \Memcached();
        $this->driver->addServer($this->config['host'], $this->config['port']);

        $ver = $this->driver->get($this->config['group']);
        if ($ver === false) {
            $ver = time();
            $this->driver->set($this->config['group'], $ver);
        }
        $this->group = $this->config['group'] . '_' . $ver;

    }

    public function get($key) {
        return $this->driver->get($this->group . '_' . $key);
    }

    public function set($key, $value, $expire = 1800) {
        return $this->driver->set($this->group . '_' . $key, $value, $expire);
    }

    public function inc($key, $value = 1) {
        return $this->driver->increment($this->group . '_' . $key, $value);
    }

    public function des($key, $value = 1) {
        return $this->driver->decrement($this->group . '_' . $key, $value);
    }

    public function del($key) {
        return $this->driver->delete($this->group . '_' . $key);
    }

    public function clear() {
        return $this->driver->increment($this->config['group']);
    }
}