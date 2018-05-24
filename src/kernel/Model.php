<?php

/**
 * 公共模型
 */

namespace dux\kernel;

class Model {

    protected $config = [];
    protected $database = 'default';
    protected $prefix = '';

    protected $options = array(
        'table' => '',
        'field' => null,
        'join' => [],
        'where' => [],
        'where_params' => [],
        'data' => [],
        'data_params' => [],
        'order' => '',
        'limit' => '',
    );

    protected $table = '';

    protected static $objArr = [];

    public function __construct($database = 'default') {
        if ($database) {
            $this->database = $database;
        }
        $config = \dux\Config::get('dux.database');
        $this->config = $config[$this->database];
        if (empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->config['type'] . ' database config error', 500);
        }
        $this->prefix = $this->config['prefix'];
    }

    public function table($table) {
        $this->options['table'] = $table;
        return $this;
    }

    public function join($table, $relation, $way = '><') {
        $this->options['join'][] = [$table, $relation, $way];
        return $this;
    }

    public function field($field) {
        $this->options['field'] = $field;
        return $this;
    }

    public function data(array $data = [], $bindParams = []) {
        $this->options['data'] = $data;
        $this->options['data_params'] = $bindParams;
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

    public function where(array $where = [], $bindParams = []) {
        $this->options['where'] = $where;
        $this->options['where_params'] = $bindParams;
        return $this;
    }

    public function select() {
        $data = $this->getObj()->select($this->_getTable().$this->_getJoin(), $this->_getWhere(), $this->_getField(), $this->_getOrder(), $this->_getLimit());
        return empty($data) ? [] : $data;
    }

    public function count() {
        return $this->getObj()->count($this->_getTable().$this->_getJoin(), $this->_getWhere());
    }

    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }

    public function insert() {
        if (empty($this->options['data']) || !is_array($this->options['data'])) return false;
        return $this->getObj()->insert($this->_getTable(), $this->_getData());
    }

    public function update() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        if (empty($this->options['data']) || !is_array($this->options['data'])) return false;
        $status = $this->getObj()->update($this->_getTable(), $this->_getWhere(), $this->_getData());
        return ($status === false) ? false : true;
    }

    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        $status = $this->getObj()->delete($this->_getTable(),  $this->_getWhere());
        return ($status === false) ? false : true;
    }

    public function setInc($field, $num = 1) {
        if( empty($this->options['where']) || !is_array($this->options['where'])  ) return false;
        if(empty($field)) return false;
        $status = $this->getObj()->increment($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : true;
    }

    public function setDec($field, $num = 1) {
        if( empty($this->options['where']) || !is_array($this->options['where'])  ) return false;
        if(empty($field)) return false;
        $status = $this->getObj()->decrease($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : true;
    }

    public function sum($field) {
        return $this->getObj()->sum($this->_getTable().$this->_getJoin(), $this->_getWhere(), $field);

    }

    public function getFields() {
        return $this->getObj()->getFields($this->table);
    }

    public function query($sql, $params = array()) {
        $sql = trim($sql);
        if ( empty($sql) ) return array();
        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->query($sql, $params);
    }

    public function execute($sql, $params = array()) {
        $sql = trim($sql);
        if ( empty($sql) ) return false;
        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->execute($sql, $params);
    }

    public function getSql() {
        return $this->getObj()->getSql();
    }

    public function beginTransaction() {
        return $this->getObj()->beginTransaction();
    }

    public function commit() {
        return $this->getObj()->commit();
    }

    public function rollBack() {
        return $this->getObj()->rollBack();
    }

    protected function getObj() {
        if (empty(self::$objArr[$this->database])) {
            $dbDriver = __NAMESPACE__ . '\model\\' . ucfirst($this->config['type']) . 'PdoDriver';
            if (!class_exists($dbDriver)) {
                throw new \Exception($this->config['type'] . ' 数据类型不存在!', 500);
            }
            self::$objArr[$this->database] = new $dbDriver($this->config);
        }
        return self::$objArr[$this->database];

    }

    protected function _getField() {
        $fields = $this->options['field'];
        $this->options['field'] = [];
        if (empty($fields)) {
            $filedSql = '*';
        } else {
            $filedSql = [];
            foreach ($fields as $vo) {
                preg_match('/([a-zA-Z0-9_\-\.\(\)]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $vo, $match);
                if (isset($match[1], $match[2])) {
                    $filedSql[] = $match[1] . ' as ' . $match[2];
                } else {
                    $filedSql[] = $vo;
                }
            }
            $filedSql = implode(',', $filedSql);
        }
        return $filedSql;
    }

    protected function _getJoin() {
        $join = $this->options['join'];
        $this->options['join'] = [];
        if (empty($join)) {
            return '';
        }
        $joinArray = array(
            '>' => 'left',
            '<' => 'right',
            '<>' => 'full',
            '><' => 'inner'
        );
        $sql = [];
        foreach ($join as $vo) {
            list($table, $relation, $way) = $vo;
            preg_match('/([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $table, $match);
            $table = $this->prefix . $match[1] . (isset($match[3]) ? ' as ' . $match[3] : '');
            if (count($relation) == 1) {
                $relation = 'USING ("' . $relation . '")';
            } else {
                $relation = $relation[0] . ' = ' . $relation[1];
            }
            $relation = 'on ' . $relation;
            $sql[] = " {$joinArray[$way]} join {$table} {$relation} ";
        }
        return implode(' ', $sql);
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

    protected function _getWhere() {
        $where = $this->options['where'];
        $this->options['where'] = [];
        return $where;
    }

    protected function _getWhereParams() {
        $where = $this->options['where_params'];
        $this->options['where_params'] = [];
        return $where;
    }

    protected function _getData() {
        $data = $this->options['data'];
        $this->options['data'] = [];
        $data = $this->_dataFilter($data);
        return $data;
    }

    protected function _getDataParams() {
        $where = $this->options['data_params'];
        $this->options['data_params'] = [];
        return $where;
    }

    protected function _getOrder() {
        $order = $this->options['order'];
        $this->options['order'] = '';
        return $order;
    }

    protected function _getLimit() {
        $limit = $this->options['limit'];
        $this->options['limit'] = [];
        if(empty($limit)) {
            return 0;
        }
        if(is_array($limit)) {
            $limit = $limit[0] . ',' . $limit[1];
        }
        return $limit;
    }

    private function _dataFilter($data = []) {
        $fields = $this->getFields();
        $array = [];
        if (empty($data)) {
            return $array;
        }
        foreach ($fields as $key) {
            if (array_key_exists($key, $data) && isset($data[$key])) {
                $array[$key] = $data[$key];
            }
        }
        return $array;
    }


}