<?php

namespace dux\com;

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
    protected $config = [];

    /**
     * 驱动配置
     */
    protected $cache = 'default';

    /**
     * 分组前缀
     */
    protected $group = 0;

    /**
     * 驱动对象
     * @var object
     */
    protected $object = null;

    /**
     * 实例化类
     * @param string $cache
     * @param array $config
     * @throws \Exception
     */
    public function __construct($cache = 'default', $group = 0) {
        if ($cache) {
            $this->cache = $cache;
        }
        $config = \dux\Config::get('dux.cache');
        $this->config = $config[$this->cache];
        $this->group = $this->config['group'];
        if($group) {
            $this->group = $group;
        }
        unset($this->config['group']);
        if (empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->cache . ' cache config error', 500);
        }
        asort($this->config);
    }

    public function get($key) {
        return $this->getObj()->getItem($this->getKey($key))->get();
    }

    public function set($key, $value, $expire = 0) {
        try {
            $item = $this->getObj()->getItem($this->getKey($key));
            $obj = $item->set($value);
            if ($expire) {
                $obj->expiresAfter($expire);
            }
            return $this->getObj()->save($item);
        }catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    public function inc($key, $value = 1) {
        try {
            $item = $this->getObj()->getItem($this->getKey($key));
            $item->increment($value);
            return $this->getObj()->save($item);
        }catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    public function dec($key, $value = 1) {
        try {
            return $this->getObj()->getItem($this->getKey($key))->decrement($value)->get();
        }catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    public function del($key) {
        return $this->getObj()->deleteItem($this->getKey($key));
    }

    public function clear() {
        return $this->getObj()->clear();
    }

    private function getKey($key) {
        return $this->group . '_' . $key;
    }

    public function getObj() {
        $class = 'cache.' . $this->cache;
        if (!di()->has($class)) {
            di()->set($class, function () {
                $config = $this->config;
                $type = $config['type'];
                unset($config['type']);
                $driver = '\\Phpfastcache\\Drivers\\' . ucfirst($type) . '\\Config';
                return \Phpfastcache\CacheManager::getInstance($type, new $driver($config));
            }, true);
        }
        $obj = di()->get($class);
        if (empty($obj)) {
            throw new \Exception($this->cache . ' cache drive does not exist', 500);
        }
        return $obj;
    }

}