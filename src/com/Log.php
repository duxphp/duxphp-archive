<?php

namespace dux\com;

use dux\com\log\LogInterface;

/**
 * 日志类
 */
class Log {

    protected $config = [];
    protected $driver = null;
    protected $object = null;

    public function __construct(string $driver, array $config = []) {
        $this->driver = $driver;
        if (!class_exists($this->driver)) {
            throw new \Exception('The log driver class does not exist', 500);
        }
        $this->config = $config;
        if (empty($this->config)) {
            throw new \Exception($this->driver . ' log config error', 500);
        }
    }

    public function items($group = 'default') {
        return $this->getObj()->items($group);
    }

    public function get($name, $group = 'default') {
        return $this->getObj()->get($name, $group);
    }

    public function set($msg, $type = 'INFO', $name = '', $group = 'default') {
        $types = ['INFO', 'WARN', 'DEBUG', 'ERROR'];
        $type = strtoupper($type);
        if (!in_array($type, $types)) {
            $type = 'INFO';
        }
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        if (empty($name)) {
            $name = date('Y-m-d');
        }
        return $this->getObj()->set($msg, $type, $name, $group);
    }

    public function del($name = '', $group = 'default') {
        return $this->getObj()->del($name, $group);
    }

    public function clear($group = 'default') {
        return $this->getObj()->clear($group);
    }

    public function getObj() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = new $this->driver($this->config);
        if (!$this->object instanceof \dux\com\log\LogInterface) {
            throw new \Exception('The log class must interface class inheritance', 500);
        }
        return $this->object;
    }

}