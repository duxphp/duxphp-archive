<?php

namespace dux\com;

/**
 * 缓存类
 *
 * @author Mr.L <admin@duxphp.com>
 */
class Cache {

    protected $object = null;
    protected $type = '';
    protected $group = 'default';
    protected $config = [];

    /**
     * 实例化类
     * @param string $type
     * @param array $config
     * @param string $group
     * @throws \Exception
     */
    public function __construct(string $type, array $config, $group = 'default') {
        $this->config = array_merge($this->config, $config);
        $this->type = $type;
        $this->group = $group;
        unset($this->config['group']);
        if (empty($this->config) || empty($type)) {
            throw new \Exception('Cache config error', 500);
        }
        asort($this->config);
    }

    /**
     * 获取缓存
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function get(string $key) {
        return $this->getObj()->getItem($this->getKey($key))->get();
    }

    /**
     * 设置缓存
     * @param string $key
     * @param $value
     * @param int $expire
     * @return mixed
     * @throws \Exception
     */
    public function set(string $key, $value, int $expire = 0) {
        $item = $this->getObj()->getItem($this->getKey($key));
        $obj = $item->set($value)->addTag('cache_' . $this->group)->expiresAfter($expire);
        return $this->getObj()->save($item);
    }

    /**
     * 递增数字
     * @param string $key
     * @param $value
     * @param int $expire
     * @return bool
     */
    public function inc(string $key, int $value = 1) {
        $item = $this->getObj()->getItem($this->getKey($key));
        $item->increment($value);
        return $this->getObj()->save($item);
    }

    /**
     * 递减数字
     * @param string $key
     * @param $value
     * @param int $expire
     * @return bool
     */
    public function dec(string $key, int $value = 1) {
        $item = $this->getObj()->getItem($this->getKey($key));
        $item->decrement($value);
        return $this->getObj()->save($item);
    }

    /**
     * 删除缓存
     * @param string $key
     * @return mixed
     * @throws \Exception
     */
    public function del(string $key) {
        return $this->getObj()->deleteItem($this->getKey($key));
    }

    /**
     * 清空缓存
     * @return mixed
     * @throws \Exception
     */
    public function clear() {
        return $this->getObj()->deleteItemsByTag('cache_' . $this->group);
    }

    /**
     * 获取键
     * @param string $key
     * @return string
     */
    private function getKey(string $key) {
        return $this->group . '_' . $key;
    }

    /**
     * 获取缓存对象
     * @return mixed
     * @throws \Exception
     */
    public function getObj() {
        if ($this->object) {
            return $this->object;
        }
        $driver = '\\Phpfastcache\\Drivers\\' . ucfirst($this->type) . '\\Config';
        if($this->type == 'files') {
            if ($this->config['path']) {
                $this->config['path'] = ROOT_ABSOLUTE_PATH . $this->config['path'];
            }else {
                $this->config['path'] = ROOT_ABSOLUTE_PATH . 'cache/tmp/';
            }
            if(!$this->config['securityKey']) {
                $this->config['securityKey'] = 'data';
            }
            if(!$this->config['cacheFileExtension']) {
                $this->config['cacheFileExtension'] = 'cache';
            }
        }
        if($this->type == 'redis') {
            if(!$this->config['host']) {
                $this->config['host'] = '127.0.0.1';
            }
            if(!$this->config['port']) {
                $this->config['port'] = 6379;
            }
            if(!$this->config['password']) {
                $this->config['password'] = '';
            }
            if(!$this->config['database']) {
                $this->config['database'] = 0;
            }
        }
        $this->object = \Phpfastcache\CacheManager::getInstance($this->type, new $driver($this->config));
        return $this->object;
    }

}