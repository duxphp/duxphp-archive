<?php

namespace dux\lib;

/**
 * Redis代理类
 * @author Mr.L <admin@duxphp.com>
 */
class Redis {

    /**
     * 数据库配置
     * @var array
     */
    protected $config = [
        'host' => 'localhost',
        'port' => 6379,
        'database' => 0,
        'password' => ''
    ];

    /**
     * Redis对象
     * @var null
     */
    protected $object = null;

    /**
     * Redis constructor.
     * @param array $config
     */
    public function __construct(array $config) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取连接对象
     * @return \Redis
     * @throws \Exception
     */
    public function link() {
        if (!$this->object) {
            $this->object = $this->linkObj();
        }
        return $this->object;
    }

    public function close() {
        if (!$this->object) {
            return;
        }
        @$this->object->close();
        $this->object = null;
    }

    /**
     * 连接对象
     * @return \Redis
     */
    private function linkObj() {
        $this->object = new \Redis();
        $ret = $this->object->connect($this->config['host'], $this->config['port']);
        if ($this->config['password']) {
            $this->object->auth($this->config['password']);
        }
        $this->object->select($this->config['database']);
        return $this->object;
    }

}