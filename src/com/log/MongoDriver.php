<?php

/**
 * 文件存储
 */

namespace dux\com\log;

class MongoDriver implements LogInterface {

    protected $obj = null;

    public function __construct($config) {
        $config['type'] = 'mongo';
        $this->config = $config;
    }

    public function items($group = '') {
        $tmp = $this->getObj()->table('log')->where(['group' => $group])->distinct('name');
        $data = [];
        foreach ($tmp as $vo) {
            $data[] = $vo['name'];
        }
        return $data;
    }

    public function get($name, $group = '') {
        $tmp = $this->getObj()->table('log')->where(['group' => $group, 'name' => $name])->select();
        $data = [];
        foreach ($tmp as $vo) {
            $data[] = [
                'time' => $vo['time'],
                'level' => $vo['level'],
                'info' => $vo['info']
            ];
        }
        return $data;
    }

    public function set($msg, $type = 'INFO', $name = '', $group = '') {
        $status = $this->getObj()->table('log')->data([
            'time' => date('Y-m-d H:i:s'),
            'level' => $type,
            'info' => $msg,
            'name' => $name,
            'group' => $group
        ])->insert();
        if ($status) {
            return true;
        }
        return false;
    }

    public function del($name = '', $group = '') {
        $status = $this->getObj()->table('log')->where(['group' => $group, 'name' => $name])->delete();
        if ($status) {
            return true;
        }
        return false;
    }

    public function clear($group = '') {
        $status = $this->getObj()->table('log')->where(['group' => $group])->delete();
        if ($status) {
            return true;
        }
        return false;
    }

    public function getObj() {
        return (new \dux\kernel\modelNo('default', $this->config))->setParams([
            'time' => [
                'type' => 'string',
            ],
            'level' => [
                'type' => 'string',
            ],
            'info' => [
                'type' => 'string',
            ],
            'group' => [
                'type' => 'string',
            ],
        ]);
    }

}