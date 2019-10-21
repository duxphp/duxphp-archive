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
        if(isset($group)) {
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
            $obj = $item->set($value)->addTag('cache_' . $this->group)->expiresAfter($expire);
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
        return $this->getObj()->deleteItemsByTag('cache_' . $this->group);
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
                if($type == 'files') {
                    $config['securityKey'] = 'data';
                    $config['cacheFileExtension'] = 'cache';
                }
                $driver = '\\Phpfastcache\\Drivers\\' . ucfirst($type) . '\\Config';
                return \Phpfastcache\CacheManager::getInstance($type, new $driver($config));
            });
        }
        $obj = di()->get($class);
        if (empty($obj)) {
            throw new \Exception($this->cache . ' cache drive does not exist', 500);
        }
        return $obj;
    }

}