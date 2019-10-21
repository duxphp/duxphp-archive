<?php

namespace dux\com;

/**
 * 日志类
 */
class Log {

    protected $config = [];

    protected $driver = 'default';

    protected $object = null;


    /**
     * 实例化类
     * @param string $log
     */
    public function __construct($driver = 'default') {
        $this->driver = $driver;
        $config = \dux\Config::get('dux.log_driver');
        $this->config = $config[$this->driver];
        if (empty($this->config) || empty($this->driver)) {
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
        $key = 'dux.log_driver.' . $this->driver;
        if (!di()->has($key)) {
            $class = __NAMESPACE__ . '\log\\' . ucfirst($this->config['type']) . 'Driver';
            di()->set($key, function () use ($class) {
                if (!class_exists($class)) {
                    throw new \Exception($this->config['type'] . ' driver does not exist', 500);
                }
                return new $class($this->config);
            });
        }
        return di()->get($key);
    }

}