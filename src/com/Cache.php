<?php

namespace dux\com;

/**
 * 缓存类
 *
 * @author Mr.L <admin@duxphp.com>
 */
class Cache {

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 缓存分组
     */
    protected $group = 'default';

    /**
     * 实例化类
     * @param array $config
     * @param $cache
     * @throws \Exception
     */
    public function __construct(array $config, $group = 'default') {
        $this->config = array_merge($this->config, $config);
        $this->group = $this->config['group'];
        if (isset($group)) {
            $this->group = $group;
        }
        unset($this->config['group']);
        if (empty($this->config) || empty($this->config['type'])) {
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
     * @return mixed|null
     * @throws \Exception
     */
    public function getObj() {
        $class = 'cache.' . http_build_query($this->config);
        if (!di()->has($class)) {
            di()->set($class, function () {
                $config = $this->config;
                $type = $config['type'];
                unset($config['type']);
                if ($type == 'files') {
                    $config['securityKey'] = 'data';
                    $config['cacheFileExtension'] = 'cache';
                }
                $driver = '\\Phpfastcache\\Drivers\\' . ucfirst($type) . '\\Config';
                return \Phpfastcache\CacheManager::getInstance($type, new $driver($config));
            });
        }
        $obj = di()->get($class);
        if (empty($obj)) {
            throw new \Exception('Cache drive does not exist', 500);
        }
        return $obj;
    }

}