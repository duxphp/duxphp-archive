<?php

/**
 * MongoDB存储驱动
 */

namespace dux\lib\storage;

class MongoDBDriver implements StorageInterface {

    protected $driver = NULL;
    protected $config = array(
        'host' => '127.0.0.1',
        'port' => 27017,
        'group' => 0,
    );

    protected $obj = null;

    public function __construct($config = array(), $group = 0) {
        $this->config = array_merge($this->config, (array)$config);
        $this->config['group'] = intval($group);
        $this->driver = new \Mongo($this->config['host'].':'. $this->config['port']);
        $tableName = "dux_" . $this->config['group'];
        $this->obj = $this->driver->dux->$tableName;
    }

    public function get($key) {
        $list = $this->obj->find(['name' => $key]);
        $info = current($list);
        if(empty($info)) {
            return false;
        }
        if($info['time'] <= time()) {
            return $this->del($key);
        }
        return $info;
    }

    public function set($key, $value, $expire = 0) {
        $data = [
            'name' => $key,
            'data' => $value,
            'time' => $expire ? time() + $expire : 0,
        ];
        return $this->obj->insert($data);
    }

    public function inc($key, $value = 1) {
        $info = $this->get($key);
        if(!$info) {
            return false;
        }
        if(!is_numeric($info['data'])) {
            return false;
        }
        return $this->obj->update(['name' => $key], array_merge($info, ['data' => $info['data'] + 1]));
    }

    public function des($key, $value = 1) {
        $info = $this->get($key);
        if(!$info) {
            return false;
        }
        if(!is_numeric($info['data'])) {
            return false;
        }
        return $this->obj->update(['name' => $key], array_merge($info, ['data' => $info['data'] - 1]));
    }

    public function del($key) {
        return $this->obj->remove(['name' => $key]);
    }

    public function clear() {
        return $this->obj->remove();
    }
}