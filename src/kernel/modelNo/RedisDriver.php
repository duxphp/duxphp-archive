<?php

/**
 * redis底层驱动
 * @author: Mr.L
 */

namespace dux\kernel\modelNo;

class RedisDriver {

    protected $config = [];
    protected $link = null;
    protected $fields = [];

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function getFields($params = []) {
        if (!empty($this->fields)) {
            return $this->fields;
        }
        $this->fields = array_keys($params);
        return $this->fields;
    }

    public function select($table, array $where, $fields = [], $order = '', $limit = 0) {
        if (empty($where)) {
            $data = $this->getLink()->sort("idx:" . $table, [
                'get' => "data:{$table}:*",
                'sort' => $order ? $order : 'ASC',
                'limit' => $limit
            ]);
        } else {
            asort($where);
            $tmpKey = 'temp:student:' . md5($table . '&' . http_build_query($where));
            $whereParams = [$tmpKey];
            foreach ($where as $key => $vo) {
                $whereParams[] = "idx:{$table}:{$key}:{$vo}";
            }
            call_user_func_array([$this->getLink(), "sunionstore"], $whereParams);
            $data = $this->getLink()->sort($tmpKey, [
                'get' => "data:{$table}:*",
                'sort' => $order ? $order : 'ASC',
                'limit' => $limit
            ]);
            $this->getLink()->del($tmpKey);
        }
        $data = array_map(function ($vo) {
            return json_decode($vo, true);
        }, $data);
        return $data;
    }

    public function count($table, array $where) {
        if (!empty(where)) {
            return count($this->select($table, $where));
        } else {
            return $this->getLink()->scard("idx:" . $table);
        }
    }
    
    public function insert($table, array $datas, $params = []) {
        foreach ($datas as $data) {
            $id = $data['id'] ? $data['id'] : $this->getId($table);
            $data['id'] = $id;
            $key = "data:{$table}:{$id}";
            $indexKey = "index:{$table}:{$id}";
            $this->getLink()->set($key, json_encode($data));
            $this->getLink()->sadd("idx:{$table}", $id);
            $this->getLink()->sadd($indexKey, "idx:" . $table);
            $index = $this->getIndex($data, $params);
            foreach ($index as $vo) {
                $data[$vo] = intval($data[$vo]);
                $this->getLink()->sadd("idx:{$table}:{$vo}:{$data[$vo]}", $id);
                $this->getLink()->sadd($indexKey, "idx:{$table}:{$vo}:{$data[$vo]}");
                $this->getLink()->sadd($indexKey, "idx:{$table}:{$vo}");
            }
        }
    }

    public function update($table, $where = [], $data = [], $params = []) {
        $oldData = $this->select($table, $where, [], '', [0, 1]);
        $oldData = $oldData[0];
        $data = array_merge((array) $oldData, (array) $data);
        $this->delete($table, $where);
        return $this->insert($table, [$data], $params);
    }

    public function delete($table, $where = []) {
        $data = $this->select($table, $where);
        $keys = array_column($data, 'id');
        foreach ($keys as $id) {
            $index = $this->getLink()->smembers("index:{$table}:{$id}");
            foreach ($index as $vo) {
                $this->getLink()->srem($vo, $id);
            }
            $this->getLink()->del("data:{$table}:{$id}");
            $this->getLink()->del("index:{$table}:{$id}");
            $this->getLink()->srem("idx:{$table}");
        }
        return true;
    }

    public function aggregate($table, array $where, $group) {
        return [];
    }

    public function distinct($table, array $where, $key) {
        return [];
    }

    public function sum($table, array $where, $field) {
        return 0;
    }

    public function setInc($collection, $where = [], $field = '', $num = 1) {
        return false;
    }

    public function setDec($collection, $where = [], $field = '', $num = 1) {
        return false;
    }

    private function getIndex($data, $params = []) {
        if (empty($params)) {
            return [];
        }
        $tmp = [];
        $paramsKey = array_keys($params);
        $paramsKey[] = 'id';
        foreach ($data as $key => $vo) {
            if (in_array($key, $paramsKey)) {
                $tmp[] = $key;
            }
        }
        return $tmp;
    }

    private function getId($table) {
        $data = $this->getLink()->smembers("idx:{$table}");
        return $data ? max($data) + 1 : 1;
    }

    public function getLink() {
        if (!$this->link) {
            $this->link = $this->_connect();
        }
        return $this->link;
    }

    protected function _connect() {
        $driver = new \Redis();
        $driver->connect($this->config['host'], $this->config['port']);
        if ($this->config['password']) {
            $driver->auth($this->config['password']);
        }
        $driver->select($this->config['dbname']);
        return $driver;
    }


}