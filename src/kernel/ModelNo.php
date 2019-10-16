<?php

/**
 * 公共Nosql模型
 */

namespace dux\kernel;

abstract class ModelNo {

    protected $config = [];
    protected $database = 'default';
    protected $prefix = '';
    protected $params = [];

    protected $options = [
        'table' => '',
        'field' => null,
        'where' => [],
        'data' => [],
        'order' => '',
        'limit' => ''
    ];

    public function __construct($database = 'default') {
        if ($database) {
            $this->database = $database;
        }
        $config = \dux\Config::get('dux.databaseNo');
        $this->config = $config[$this->database];
        if (empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->config['type'] . ' database config error', 500);
        }
        $this->prefix = empty($this->config['prefix']) ? '' : $this->config['prefix'];
    }

    public function table($table) {
        $this->options['table'] = $table;
        return $this;
    }

    public function field($field) {
        $this->options['field'] = $field;
        return $this;
    }

    public function data(array $data = []) {
        $this->options['data'] = $data;
        return $this;
    }

    public function order($order) {
        $this->options['order'] = $order;
        return $this;
    }

    public function limit($limit) {
        $this->options['limit'] = $limit;
        return $this;
    }

    public function where(array $where = []) {
        $this->options['where'] = $where;
        return $this;
    }

    public function select() {
        $data = $this->getObj()->select($this->_getTable(), $this->_getWhere(), $this->_getField(), $this->_getOrder(), $this->_getLimit());
        return empty($data) ? [] : $data;
    }

    public function count() {
        $count = $this->getObj()->count($this->_getTable(), $this->_getWhere());
        return $count ? $count : 0;
    }

    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }

    public function insert($data = []) {
        $ids = $this->insertAll($data);
        if (!$ids) {
            return false;
        }
        return $ids[0];
    }

    public function insertAll($data = []) {
        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }
        $table = $this->_getTable();
        $datas = $this->_getData();
        if (empty($datas) || !is_array($datas)) {
            return false;
        }
        if (!isset($datas[0])) {
            $datas = [$datas];
        }
        $id = $this->getObj()->insert($table, $datas, $this->params);
        if ($ids === false) {
            return false;
        }
        return $ids;
    }

    public function update() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }
        $table = $this->_getTable();
        $datas = $this->_getData();
        $where = $this->_getWhere();
        if (empty($datas) || !is_array($datas)) {
            return false;
        }
        return $this->getObj()->update($table, $where, $datas, $this->params);
    }

    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->delete($this->_getTable(), $this->_getWhere());
        return ($status === false) ? false : $status;
    }

    public function setInc($field, $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->setInc($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : $status;
    }

    public function setDec($field, $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->setDec($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : $status;
    }

    public function group($group) {
        return $db->group($this->_getTable(), $this->_getWhere(), $group);
    }

    public function distinct($group) {
        return $db->distinct($this->_getTable(), $this->_getWhere(), $group);
    }

    public function sum($field) {
        $sum = $db->sum($this->_getTable(), $this->_getWhere(), $field);
        return empty($sum) ? 0 : $sum;
    }

    public function query($table, $where, $options) {
        return $this->getObj()->query($table, $where, $options);
    }

    public function getFields() {
        return $this->getObj()->getFields($this->params);
    }

    public function getPrimary() {
        return $this->getObj()->getPrimary();
    }

    protected function _getField() {
        $fields = $this->options['field'];
        $this->options['field'] = [];
        return $fields;
    }

    protected function _getWhere() {
        $where = $this->options['where'];
        $this->options['where'] = [];
        return $where;
    }

    protected function _getTable() {
        $table = $this->options['table'];
        $this->options['table'] = '';
        if (empty($table)) {
            $class = get_called_class();
            $class = str_replace('\\', '/', $class);
            $class = basename($class);
            $class = substr($class, 0, -5);
            $class = preg_replace("/(?=[A-Z])/", "_\$1", $class);
            $class = substr($class, 1);
            $class = strtolower($class);
            $table = $class;
        } else {
            preg_match('/([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $table, $match);
            $table = trim($match[1]) . (isset($match[3]) ? ' as ' . $match[3] : '');
        }
        $table = $this->prefix . $table;
        $this->table = $table;
        return $table;
    }

    protected function _getData() {
        $data = $this->options['data'];
        $this->options['data'] = [];
        return $data;
    }

    protected function _getOrder() {
        $order = $this->options['order'];
        $this->options['order'] = '';
        return $order;
    }

    protected function _getLimit() {
        $limit = $this->options['limit'];
        $this->options['limit'] = [];
        if (empty($limit)) {
            return 0;
        }
        if (!is_array($limit)) {
            $limit = explode(',', $limit);
        }
        if (count($limit) == 1) {
            $limitArr = [0, (int)$limit[0]];
        } else {
            $limitArr = [(int)$limit[0], (int)$limit[1]];
        }
        return $limitArr;
    }

    protected function getObj() {
        $dbDriver = __NAMESPACE__ . '\modelNo\\' . ucfirst($this->config['type']) . 'Driver';
        if (!di()->has($this->database)) {
            di()->set($this->database, function () use ($dbDriver) {
                if (!class_exists($dbDriver)) {
                    throw new \Exception($this->config['type'] . ' 数据类型不存在!', 500);
                }
                return new $dbDriver($configName, $this->config);
            }, true);
        }
        return di()->get($dbDriver);
    }

}