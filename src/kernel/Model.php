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
        'lock' => false,
        'join' => [],
        'where' => [],
        'where_params' => [],
        'data' => [],
        'data_params' => [],
        'group' => '',
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

    public function lock($lock = true) {
        $this->options['lock'] = $lock;
        return $this;
    }

    public function order($order) {
        $this->options['order'] = $order;
        return $this;
    }

    public function group($group) {
        $this->options['group'] = $group;
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
        $data = $this->getObj()->select($this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getWhereParams(), $this->_getField(), $this->_getLock(), $this->_getOrder(), $this->_getLimit(), $this->_getGroup());
        return empty($data) ? [] : $data;
    }

    public function count() {
        return $this->getObj()->count($this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getWhereParams(), $this->_getGroup());
    }

    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }

    public function insert() {
        if (empty($this->options['data']) || !is_array($this->options['data'])) return false;
        return $this->getObj()->insert($this->_getTable(), $this->_getData(), $this->_getDataParams());
    }

    public function update() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        if (empty($this->options['data']) || !is_array($this->options['data'])) return false;
        $status = $this->getObj()->update($this->_getTable(), $this->_getWhere(), $this->_getWhereParams(), $this->_getData(), $this->_getDataParams());
        return ($status === false) ? false : true;
    }

    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        $status = $this->getObj()->delete($this->_getTable(), $this->_getWhere(), $this->_getWhereParams());
        return ($status === false) ? false : true;
    }

    public function setInc($field, $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        if (empty($field)) return false;
        $status = $this->getObj()->increment($this->_getTable(), $this->_getWhere(), $this->_getWhereParams(), $field, $num);
        return ($status === false) ? false : true;
    }

    public function setDec($field, $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) return false;
        if (empty($field)) return false;
        $status = $this->getObj()->decrease($this->_getTable(), $this->_getWhere(), $this->_getWhereParams(), $field, $num);
        return ($status === false) ? false : true;
    }

    public function sum($field) {
        return $this->getObj()->sum($this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getWhereParams(), $field);

    }

    public function getFields() {
        return $this->getObj()->getFields($this->_getTable());
    }

    public function query($sql, $params = array()) {
        $sql = trim($sql);
        if (empty($sql)) return array();
        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->query($sql, $params);
    }

    public function execute($sql, $params = array()) {
        $sql = trim($sql);
        if (empty($sql)) return false;
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

    protected function _getLock() {
        $lock = $this->options['lock'];
        $this->options['lock'] = [];
        return $lock;
    }

    protected function _getWhere() {
        $condition = $this->options['where'];
        $this->options['where'] = [];
        $sql = $this->_whereParsing($condition, $this->options['where_params'], ' AND');
        return $sql ? ' WHERE ' . $sql : '';
    }


    protected function columnQuote($string) {
        if (strpos($string, '.') !== false) {
            return '"' . $this->prefix . str_replace('.', '"."', $string) . '"';
        }

        return '"' . $string . '"';
    }

    protected function _getWhereParams() {
        $where = $this->options['where_params'];
        $this->options['where_params'] = [];
        return $where;
    }

    protected function _getData() {
        $data = $this->options['data'];
        $this->options['data'] = [];
        $data = $this->_dataParsing($data);
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

    protected function _getGroup() {
        $order = $this->options['group'];
        $this->options['group'] = '';
        return $order;
    }

    protected function _getLimit() {
        $limit = $this->options['limit'];
        $this->options['limit'] = [];
        if (empty($limit)) {
            return 0;
        }
        if (is_array($limit)) {
            $limit = $limit[0] . ',' . $limit[1];
        }
        return $limit;
    }

    private function _whereParsing($data, &$map, $conjunctor) {
        $stack = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)) {
                $relationship = $relation_match[1];
                $stack[] = $value !== array_keys(array_keys($value)) ? '(' . $this->_whereParsing($value, $map, ' ' . $relationship) . ')' : '(' . $this->_whereConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';
                continue;
            }
            if (strtolower($key) == '_sql') {
                if (is_array($value)) {
                    foreach ($value as $s) {
                        $stack[] = $s;
                    }
                } else {
                    $stack[] = $value;
                }
            } else {
                if (is_int($key) && preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)) {
                    $stack[] = "{$match[1]} {$match['operator']} {$match[3]}";
                } else {
                    preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
                    $key = str_replace('`', '', $match[1]);
                    $field = '`' . str_replace('.', '`.`', $key) . '`';
                    $bindField = ':_where_' . str_replace('.', '_', $key);

                    if (isset($match['operator'])) {
                        $operator = $match['operator'];

                        if (in_array($operator, ['>', '>=', '<', '<='])) {
                            $stack[] = "{$field} {$operator} {$bindField}";
                            $map[$bindField] = $value;
                        } elseif ($operator === '!') {
                            if (is_array($value)) {
                                $stack[] = $field . ' NOT IN (' . implode(', ', $value) . ')';
                            } else {
                                $stack[] = "{$field} != {$bindField}";
                                $map[$bindField] = $value;
                            }
                        } elseif ($operator === '~' || $operator === '!~') {
                            if (!is_array($value)) {
                                $value = [$value];
                            }
                            $like_clauses = [];
                            foreach ($value as $index => $item) {
                                $item = strval($item);

                                if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item)) {
                                    $item = '%' . $item . '%';
                                }
                                $like_clauses[] = $field . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $bindField . '_' . $index;
                                $map[$bindField . '_' . $index] = $item;
                            }

                            $stack[] = '(' . implode($connector, $like_clauses) . ')';

                        } elseif ($operator === '<>' || $operator === '><') {
                            if (is_array($value)) {

                                $stack[] = '(' . $field . ($operator === '><' ? ' NOT' : '') . ' BETWEEN ' . $bindField . '_a AND ' . $bindField . '_b)';
                                $map[$bindField . '_a'] = $value[0];
                                $map[$bindField . '_b'] = $value[0];
                            }
                        } elseif ($operator === 'REGEXP') {
                            $stack[] = $key . ' REGEXP ' . $bindField;
                            $map[$bindField] = $value;
                        }
                    } else {
                        if (is_array($value)) {
                            $stack[] = $field . ' IN (' . implode(', ', $value) . ')';
                        } else {
                            $stack[] = "{$field} = {$bindField}";
                            $map[$bindField] = $value;
                        }
                    }
                }
            }
        }
        return implode($conjunctor . ' ', $stack);
    }

    private function _whereConjunct($data, $map, $conjunctor, $outer_conjunctor) {
        $stack = [];
        foreach ($data as $value) {
            $stack[] = '(' . $this->_whereParsing($value, $map, $conjunctor) . ')';
        }
        return implode($outer_conjunctor . ' ', $stack);
    }

    private function _dataParsing($data = [], $type = 0) {
        $fields = [];
        $sql = [];
        $map = [];
        $tableField = $this->getObj()->getFields($this->table);
        foreach ($data as $key => $value) {
            $column = preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key);
            $bindField = ':_data_' . str_replace('.', '_', $column);
            if(!in_array($column, $tableField)) {
                unset($data[$key]);
                continue;
            }
            $fields[$column] = $bindField;
            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);
            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $sql[] = '`'.$column.'`' . ' = ' . $column . ' ' . $match['operator'] . ' ' . $value;
                }
            } else {
                $sql[] = '`'.$column.'`' . ' = ' . $bindField;
                if (is_array($value)) {
                    $map[$bindField] = strpos($key, '[JSON]') === strlen($key) - 6 ? json_encode($value) : serialize($value);
                } else {
                    $map[$bindField] = $value;
                }
            }
        }
        $this->options['data_params'] = $map;
        return [
            'sql' => $sql,
            'data' => $fields,
        ];
    }


}
