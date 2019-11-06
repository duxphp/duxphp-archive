<?php

/**
 * Redis存储
 */

namespace dux\com\log;

class RedisDriver implements LogInterface {

    protected $obj = null;
    protected $indexKey = '';
    protected $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'dbname' => 0,
        'prefix' => 'dux_'
    ];

    public function __construct($config) {
        $this->config = array_merge($this->config, $config);
        $this->indexKey = "log_index:{$this->config['prefix']}";
    }

    public function items($group = '') {
        $tmp = $this->getObj()->smembers($this->indexKey . $group);
        $data = [];
        foreach ($tmp as $vo) {
            $str = explode(':', $vo, 3);
            $str = str_replace($this->config['prefix'], '', end($str));
            $data[] = $str;
        }
        return $data;
    }

    public function get($name, $group = '') {
        $key = "log:{$group}:{$this->config['prefix']}{$name}";
        $tmp = $this->getObj()->lrange($key, 0, -1);
        $data = [];
        foreach ($tmp as $vo) {
            $vo = json_decode($vo, true);
            $data[] = [
                'time' => $vo['time'],
                'level' => $vo['level'],
                'info' => $vo['info']
            ];
        }
        return $data;
    }

    public function set($msg, $type = 'INFO', $name = '', $group = '') {
        $data = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'level' => $type,
            'info' => $msg,
        ]);
        $key = "log:{$group}:{$this->config['prefix']}{$name}";
        $this->getObj()->rpush($key, $data);
        $status = $this->getObj()->sadd($this->indexKey . $group, $key);
        if ($status) {
            return true;
        }
        return false;
    }

    public function del($name = '', $group = '') {
        $key = "log:{$group}:{$this->config['prefix']}{$name}";
        $this->getObj()->del($key);
        $status = $this->getObj()->srem($this->indexKey . $group, $key);
        if ($status) {
            return true;
        }
        return false;
    }

    public function clear($group = '') {
        $tmp = $this->getObj()->smembers($this->indexKey . $group);
        foreach ($tmp as $vo) {
            $this->getObj()->del($vo);
        }
        $status = $this->getObj()->del($this->indexKey . $group);
        if ($status) {
            return true;
        }
        return false;
    }

    public function getObj() {
        return (new \dux\kernel\modelNo('default', $this->config))->getObj()->getLink();
    }

}