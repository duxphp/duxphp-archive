<?php

/**
 * 公共模型
 */

namespace dux\kernel;

class Model {

    protected $config = [];
    protected $database = 'default';
    protected $prefix = '';
    protected $table = '';
    protected static $objArr = [];

    protected $options = [
        'table' => '',
        'field' => null,
        'lock' => false,
        'join' => [],
        'where' => [],
        'data' => [],
        'bind_params' => [],
        'append' => [],
        'order' => '',
        'limit' => '',
        'return' => false,
        'original' => false,
    ];

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
        $this->options['bind_params'] = $bindParams;
        return $this;
    }

    public function lock($lock = true) {
        $this->options['append']['lock'] = $lock;
        return $this;
    }

    public function order($order) {
        $this->options['append']['order'] = $order;
        return $this;
    }

    public function group($group) {
        $this->options['append']['group'] = $group;
        return $this;
    }

    public function limit($limit) {
        $this->options['append']['limit'] = $limit;
        return $this;
    }

    public function original($original = true) {
        $this->options['original'] = $original;
        return $this;
    }

    public function fetchSql($status = true) {
        $this->options['return'] = $status;
        return $this;
    }

    public function where(array $where = [], $bindParams = []) {
        $this->options['where'] = $where;
        $this->options['bind_params'] = $bindParams;
        return $this;
    }

    public function select() {
        $data = $this->getObj()->select($this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
        return empty($data) ? [] : $data;
    }

    public function count() {
        return $this->getObj()->aggregate('COUNT', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
    }

    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }

    protected function columnQuote($string) {
        if (strpos($string, '.') !== false) {
            return '`' . $this->prefix . str_replace('.', '".`', $string) . '`';
        }
        return '`' . $string . '`';
    }

    public function insert() {
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
        $stack = [];
        $columns = [];
        foreach ($datas as $key => $data) {
            $dataParams = $this->_dataParsing($data, $key);
            $columns = array_merge($columns, $dataParams['fields']);
            $stack[] = '(' . implode(', ', $dataParams['stack']) . ')';
        }
        $columns = array_unique($columns);
        return $this->getObj()->insert($table, $columns, $stack, $this->_getBindParams(), $this->_getFetchSql());
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
        $dataParams = $this->_dataParsing($datas);
        $stack = $dataParams['stack'];
        $columns = array_unique($dataParams['fields']);
        $status = $this->getObj()->update($table, $where, $columns, $stack, $this->_getBindParams(), $this->_getFetchSql());
        if ($this->_getOriginal()) {
            return $status;
        }
        return ($status === false) ? false : true;
    }

    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->delete($this->_getTable(), $this->_getWhere(), $this->_getBindParams(), $this->_getFetchSql());
        if ($this->_getOriginal()) {
            return $status;
        }
        return ($status === false) ? false : true;
    }

    public function setInc($field, $num = 1) {
        return $this->data([
            $field . '[+]' => $num,
        ])->update();
    }

    public function setDec($field, $num = 1) {
        return $this->data([
            $field . '[-]' => $num,
        ])->update();
    }

    public function sum($field = '') {
        $this->field($field);
        return $this->getObj()->aggregate('SUM', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
    }

    public function avg($field = '') {
        $this->field($field);
        return $this->getObj()->aggregate('AVG', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
    }

    public function max($field = '') {
        $this->field($field);
        return $this->getObj()->aggregate('MAX', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
    }

    public function min($field = '') {
        $this->field($field);
        return $this->getObj()->aggregate('MIN', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $this->_getField(), $this->_getAppend(), $this->_getFetchSql());
    }

    public function getFields() {
        return $this->getObj()->getFields($this->_getTable());
    }

    public function query($sql, $params = []) {
        $sql = trim($sql);
        if (empty($sql)) {
            return [];
        }

        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->query($sql, $params, $this->_getFetchSql());
    }

    public function execute($sql, $params = []) {
        $sql = trim($sql);
        if (empty($sql)) {
            return false;
        }

        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->execute($sql, $params, $this->_getFetchSql());
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
        } else if (is_string($fields)) {
            $filedSql = $fields;
        } else {
            $filedSql = [];
            foreach ($fields as $key => $vo) {
                if (is_string($key)) {
                    $filedSql[] = $vo . ' AS ' . $this->columnQuote($key);
                } else {
                    preg_match('/([a-zA-Z0-9_\-\.\(\)]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $vo, $match);
                    if (isset($match[1], $match[2]) && !in_array(strtolower($match[1]), ['min', 'max', 'avg', 'sum', 'count'])) {
                        $filedSql[] = $match[1] . ' as ' . $this->columnQuote($match[2]);
                    } else {
                        $filedSql[] = $vo;
                    }
                }
            }
            $filedSql = implode(',', $filedSql);
        }
        $filedSql = str_replace('{pre}', $this->config['prefix'], $filedSql);
        return $filedSql;
    }

    protected function _getJoin() {
        $join = $this->options['join'];
        $this->options['join'] = [];
        if (empty($join)) {
            return '';
        }
        $joinArray = [
            '>' => 'LEFT',
            '<' => 'RIGHT',
            '<>' => 'FULL',
            '><' => 'INNER',
        ];
        $sql = [];
        foreach ($join as $vo) {
            list($table, $relation, $way) = $vo;
            preg_match('/([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $table, $match);
            $table = $this->prefix . $match[1] . (isset($match[3]) ? ' as ' . $match[3] : '');
            if (!$relation[0]) {
                $str = [];
                foreach ($relation as $k => $v) {
                    if ($k == '_sql') {
                        $str[] = $v;
                    } else {
                        $str[] = $k . ' = ' . $v;
                    }
                }
                $relation = implode(' AND ', $str);
            } else {
                if (count($relation) == 1) {
                    $relation = 'USING ("' . $relation . '")';
                } else {
                    $relation = $relation[0] . ' = ' . $relation[1];
                }
            }
            $relation = 'on ' . $relation;
            $sql[] = " {$joinArray[$way]} JOIN {$table} {$relation} ";
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

    protected function _getOriginal() {
        $return = $this->options['original'];
        $this->options['original'] = false;
        return $return;
    }

    protected function _getFetchSql() {
        $return = $this->options['return'];
        $this->options['return'] = false;
        return $return;
    }

    protected function _getWhere() {
        $condition = $this->options['where'];
        $this->options['where'] = [];
        return $this->_whereParsing($condition, $this->options['bind_params'], ' AND ');
    }

    protected function _getBindParams() {
        $where = $this->options['bind_params'];
        $this->options['bind_params'] = [];
        return $where;
    }

    protected function _getData() {
        $data = $this->options['data'];
        $this->options['data'] = [];
        return $data;
    }

    protected function _getAppend() {
        $append = $this->options['append'];
        $appendData = [];
        foreach ($append as $key => $vo) {
            if ($key == 'group' && $vo) {
                $appendData[1] = ' GROUP BY ' . (is_array($vo) ? implode(',', $vo) : $vo);
            }
            if ($key == 'order' && $vo) {
                $appendData[2] = ' ORDER BY ' . (is_array($vo) ? implode(',', $vo) : $vo);
            }
            if ($key == 'having' && $vo) {
                $appendData[0] = ' HAVING ' . $vo;
            }
            if ($key == 'lock' && $vo == true) {
                $appendData[4] = ' FOR UPDATE ';
            }
            if ($key == 'limit' && $vo) {
                $appendData[3] = ' LIMIT ' . (is_array($vo) ? implode(',', $vo) : ($vo ? $vo : 0));
            }
        }
        $this->options['append'] = [];
        ksort($appendData);
        return $appendData ? implode(' ', $appendData) : '';
    }

    private function _whereParsing($data, &$map, $conjunctor, $inheritField = '') {
        $stack = [];
        $i = 0;
        foreach ($data as $key => $value) {
            $tmpField = $inheritField . '_' . $i;
            $i++;
            if (is_array($value) && preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)) {
                $relationship = $relation_match[1];
                $stack[] = $value !== array_keys(array_keys($value)) ? '(' . $this->_whereParsing($value, $map, ' ' . $relationship, $tmpField) . ')' : '(' . $this->_whereConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';
                continue;
            }
            if (strtolower($key) == '_sql') {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $s) {
                    $stack[] = $s;
                }
            } else {
                if (is_int($key) && preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)) {
                    $stack[] = "{$match[1]} {$match['operator']} {$match[3]}";
                } else {
                    preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
                    $key = str_replace('`', '', $match[1]);
                    $field = '`' . str_replace('.', '`.`', $key) . '`';

                    $bindField = ':_where_' . str_replace('.', '_', $key) . $tmpField . '_' . $i;

                    if (isset($match['operator'])) {
                        $operator = $match['operator'];
                        if (in_array($operator, ['>', '>=', '<', '<='])) {
                            $stack[] = "{$field} {$operator} {$bindField}";
                            $map[$bindField] = $value;
                        } else if ($operator === '!') {
                            if (is_array($value)) {
                                foreach ($value as $k => $v) {
                                    $value[$k] = "'" . $v . "'";
                                }
                                $stack[] = $field . ' NOT IN (' . implode(', ', $value) . ')';
                            } else {
                                $stack[] = "{$field} != {$bindField}";
                                $map[$bindField] = $value;
                            }
                        } else if ($operator === '~' || $operator === '!~') {
                            if (!is_array($value)) {
                                $value = [$value];
                            }
                            $connector = ' OR ';
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

                        } else if ($operator === '<>' || $operator === '><') {
                            if (is_array($value)) {
                                $stack[] = '(' . $field . ($operator === '><' ? ' NOT' : '') . ' BETWEEN ' . $bindField . '_a AND ' . $bindField . '_b)';
                                $map[$bindField . '_a'] = $value[0];
                                $map[$bindField . '_b'] = $value[1];
                            }
                        } else if ($operator === 'REGEXP') {
                            $stack[] = $key . ' REGEXP ' . $bindField;
                            $map[$bindField] = $value;
                        }
                    } else {
                        if (is_array($value)) {
                            foreach ($value as $k => $v) {
                                $value[$k] = "'" . $v . "'";
                            }
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

    private function _dataParsing($data = [], $inheritField = '') {
        $stack = [];
        $fields = [];
        $tableField = $this->getObj()->getFields($this->table);
        $restData = [];
        if (empty($data)) {
            return $restData;
        }
        foreach ($data as $key => $value) {
            $column = preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key);
            $bindField = ':_data_' . ($inheritField ? $inheritField . '_' : '') . str_replace('.', '_', $column);
            if (!in_array($column, $tableField) || !isset($value)) {
                continue;
            }
            $column = $this->columnQuote($column);
            $fields[] = $column;
            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);
            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $stack[] = $column . $match['operator'] . ' ' . $value;
                }
            } else {
                $stack[] = $bindField;
                if (is_array($value)) {
                    $this->options['bind_params'][$bindField] = json_encode($value, JSON_UNESCAPED_UNICODE);
                } else {
                    $this->options['bind_params'][$bindField] = $value;
                }
            }
        }
        return [
            'stack' => $stack,
            'fields' => $fields,
        ];
    }

}
