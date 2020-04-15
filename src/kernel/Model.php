<?php

/**
 * 公共模型
 */

namespace dux\kernel;

use PDO;
use PDOException;

class Model {

    protected $driver = null;
    protected $object = null;
    protected $config = [];
    protected $guid = 0;

    public $prefix = '';

    protected $options = [
        'table' => [],
        'field' => [],
        'lock' => false,
        'join' => [],
        'where' => [],
        'data' => [],
        'map' => [],
        'append' => [],
        'order' => '',
        'limit' => '',
        'page' => [],
        'return' => false,
        'raw' => false,
    ];

    /**
     * 模型初始化
     * @param string $driver
     * @param array $config
     * @throws \Exception
     */
    public function __construct(string $driver = '', array $config = []) {
        $this->driver = $driver ?: $this->driver;
        if (!class_exists($this->driver)) {
            throw new \Exception('The database driver class does not exist', 500);
        }
        $this->config = $config ?: $this->config;
        $this->prefix = $this->config['prefix'];
        if (empty($this->config)) {
            throw new \Exception($this->driver . ' database config error', 500);
        }
    }

    /**
     * 设置参数
     * @param array $params
     * @return $this
     */
    public function setParams(array $params) {
        $this->options = $params;
        return $this;
    }

    /**
     * 设置前缀
     * @param string $pre
     * @return $this
     */
    public function setPrefix(string $pre) {
        $this->prefix = $pre;
        return $this;
    }

    /**
     * 设置配置
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config) {
        $this->config = $config;
        return $this;
    }

    /**
     * 设置表
     * @param string $table
     * @return $this
     */
    public function table(string $table) {
        preg_match('/([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $table, $match);
        $this->options['table'] = [$match[1], $match[3]];
        return $this;
    }

    /**
     * 关联表
     * @param string $table
     * @param $relation
     * @param string $way
     * @return $this
     */
    public function join(string $table, $relation, string $way = '><') {
        preg_match('/(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $table, $match);
        $this->options['join'][] = [$match['table'], $match['alias'], $relation, $way];
        return $this;
    }

    /**
     * 设置字段
     * @param $field
     * @return $this
     */
    public function field($field) {
        $this->options['field'] = $field;
        return $this;
    }

    /**
     * 设置数据
     * @param array $data
     * @param array $bindParams
     * @return $this
     */
    public function data(array $data = []) {
        $this->options['data'] = $data;
        return $this;
    }

    /**
     * 设置行锁
     * @param bool $lock
     * @return $this
     */
    public function lock(bool $lock = true) {
        $this->options['append']['lock'] = $lock;
        return $this;
    }

    /**
     * 排序
     * @param string $order
     * @return $this
     */
    public function order(?string $order) {
        $this->options['append']['order'] = $order;
        return $this;
    }

    /**
     * 分组
     * @param string $group
     * @return $this
     */
    public function group(string $group) {
        $this->options['append']['group'] = $group;
        return $this;
    }

    /**
     * 设置数量
     * @param $limit
     * @return $this
     */
    public function limit($limit) {
        $this->options['append']['limit'] = $limit;
        return $this;
    }

    /**
     * 返回原数据
     * @param bool $raw
     * @return $this
     */
    public function raw(bool $raw = true) {
        $this->options['raw'] = $raw;
        return $this;
    }

    /**
     * 预执行Sql
     * @param bool $status
     * @return $this
     */
    public function fetchSql(bool $status = true) {
        $this->options['return'] = $status;
        return $this;
    }

    /**
     * 设置条件
     * @param array $where
     * @param array $bindParams
     * @return $this
     */
    public function where(array $where = [], array $bindParams = []) {
        $maps = [];
        foreach ($bindParams as $key => $value) {
            if (strpos($key, ':', 0) === false) {
                $key = ':' . $key;
            }
            $maps[$key] = is_array($value) ? $value : $this->typeMap($value, gettype($value));
        }
        $this->options['where'] = $where;
        $this->options['map'] = $maps;
        return $this;
    }

    /**
     * 查询多条数据
     * @return array|mixed
     * @throws \Exception
     */
    public function select() {
        $field = $this->_getField();
        $table = $this->_getTable();
        $join = $this->_getJoin();
        $data = $this->getObj()->select($table . $join, $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
        $data = empty($data) ? [] : $data;
        $result = [];
        $columnMap = [];
        $this->columnMap($field['column'], $columnMap);
        $currentStack = [];
        $result = [];
        foreach ($data as $vo) {
            $result[] = $this->dataMap($vo, $field['column'], $columnMap);
        }
        return $result;
    }


    /**
     * 统计数量
     * @return mixed
     * @throws \Exception
     */
    public function count() {
        $field = $this->_getField();
        return (int)$this->getObj()->aggregate('COUNT', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
    }

    /**
     * 查询单条
     * @return array
     * @throws \Exception
     */
    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }


    /**
     * 插入数据
     * @return bool|mixed
     * @throws \Exception
     */
    public function insert() {
        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }

        $table = $this->_getTable();
        $datas = $this->_getData();
        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];

        if (!isset($datas[0])) {
            $datas = [$datas];
        }

        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }

        $columns = array_unique($columns);
        foreach ($datas as $data) {
            $values = [];
            foreach ($columns as $key) {
                if (strtoupper(substr($key, -5)) === '[SQL]') {
                    $values[] = $this->buildRaw($this->_columnQuote(substr($key, 0, -5)) . ' = ' . $data[$key], $map);
                    continue;
                }
                $mapKey = $this->mapKey();
                $values[] = $mapKey;
                if (!isset($data[$key])) {
                    $map[$mapKey] = [null, PDO::PARAM_NULL];
                } else {
                    $value = $data[$key];
                    $type = gettype($value);
                    switch ($type) {
                        case 'array':
                            $map[$mapKey] = [
                                strpos($key, '[ARRAY]') === strlen($key) - 7 ?
                                    serialize($value) :
                                    json_encode($value)
                                ,
                                PDO::PARAM_STR
                            ];
                            break;
                        case 'object':
                            $value = serialize($value);
                        case 'NULL':
                        case 'resource':
                        case 'boolean':
                        case 'integer':
                        case 'double':
                        case 'string':
                            $map[$mapKey] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
            $stack[] = '(' . implode(', ', $values) . ')';
        }

        foreach ($columns as $key) {
            $fields[] = $this->_columnQuote(preg_replace("/(\s*\[(ARRAY|SQL)\]$)/i", '', $key));
        }

        $id = $this->getObj()->insert($table, $fields, $stack, $map, $this->_getFetchSql());
        if ($id === false) {
            dux_error('Insert the data failure');
        }
        return $id;
    }

    /**
     * 更新数据
     * @return bool|mixed
     * @throws \Exception
     */
    public function update() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }
        $table = $this->_getTable();
        $data = $this->_getData();
        $where = $this->_getWhere();
        $map = $this->_getBindParams();
        if (empty($data) || !is_array($data)) {
            return false;
        }
        $fields = [];
        foreach ($data as $key => $value) {
            $column = $this->_columnQuote(preg_replace("/(\s*\[(ARRAY|SQL|\+|\-|\*|\/)\]$)/i", '', $key));
            if (strtoupper(substr($key, -5)) === '[SQL]') {
                $fields[] = $this->buildRaw($column . ' = ' . $data[$key], $map);
                continue;
            }
            $map_key = $this->mapKey();
            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);
            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = $column . ' = ' . $column . ' ' . $match['operator'] . ' ' . $value;
                }
            } else {
                $fields[] = $column . ' = ' . $map_key;
                $type = gettype($value);
                switch ($type) {
                    case 'array':
                        $map[$map_key] = [
                            strpos($key, '[ARRAY]') === strlen($key) - 7 ?
                                serialize($value) :
                                json_encode($value)
                            ,
                            PDO::PARAM_STR
                        ];
                        break;
                    case 'object':
                        $value = serialize($value);
                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[$map_key] = $this->typeMap($value, $type);
                        break;
                }
            }
        }

        $status = $this->getObj()->update($table, $where, $fields, $map, $this->_getFetchSql());
        if ($this->_getRaw()) {
            return $status;
        }
        if ($status === false) {
            dux_error('Update the data failure');
        }
        return ($status === false) ? false : true;
    }

    /**
     * 删除数据
     * @return bool|mixed
     * @throws \Exception
     */
    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->delete($this->_getTable(), $this->_getWhere(), $this->_getBindParams(), $this->_getFetchSql());
        if ($this->_getRaw()) {
            return $status;
        }
        if ($status === false) {
            dux_error('Delete the data failure');
        }
        return ($status === false) ? false : true;
    }

    /**
     * 递增数据
     * @param string $field
     * @param int $num
     * @return bool|mixed
     * @throws \Exception
     */
    public function setInc(string $field, int $num = 1) {
        return $this->data([
            $field . '[+]' => $num,
        ])->update();
    }

    /**
     * 递减数据
     * @param string $field
     * @param int $num
     * @return bool|mixed
     * @throws \Exception
     */
    public function setDec(string $field, int $num = 1) {
        return $this->data([
            $field . '[-]' => $num,
        ])->update();
    }

    /**
     * 求和
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function sum(string $field = '') {
        $this->field($field);
        $field = $this->_getField();
        return $this->getObj()->aggregate('SUM', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
    }

    /**
     * 平均值
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function avg(string $field = '') {
        $this->field($field);
        $field = $this->_getField();
        return $this->getObj()->aggregate('AVG', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
    }

    /**
     * 最大值
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function max(string $field = '') {
        $this->field($field);
        $field = $this->_getField();
        return $this->getObj()->aggregate('MAX', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
    }

    /**
     * 最小值
     * @param string $field
     * @return mixed
     * @throws \Exception
     */
    public function min(string $field = '') {
        $this->field($field);
        $field = $this->_getField();
        return $this->getObj()->aggregate('MIN', $this->_getTable() . $this->_getJoin(), $this->_getWhere(), $this->_getBindParams(), $field['sql'], $this->_getAppend(), $this->_getFetchSql());
    }

    /**
     * 获取字段
     * @return mixed
     * @throws \Exception
     */
    public function getFields() {
        return $this->getObj()->getFields($this->_getTable());
    }

    /**
     * 原生查询
     * @param string $sql
     * @param array $params
     * @return array|mixed
     * @throws \Exception
     */
    public function query(string $sql, array $params = []) {
        $sql = trim($sql);
        if (empty($sql)) {
            return [];
        }

        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->query($sql, $params, $this->_getFetchSql());
    }

    /**
     * 原生执行
     * @param string $sql
     * @param array $params
     * @return bool|mixed
     * @throws \Exception
     */
    public function execute(string $sql, array $params = []) {
        $sql = trim($sql);
        if (empty($sql)) {
            return false;
        }

        $sql = str_replace('{pre}', $this->config['prefix'], $sql);
        return $this->getObj()->execute($sql, $params, $this->_getFetchSql());
    }

    /**
     * 获取最后一次Sql
     * @return string
     * @throws \Exception
     */
    public function getSql() {
        return $this->getObj()->getSql();
    }

    /**
     * 开启事务
     * @return bool
     * @throws \Exception
     */
    public function beginTransaction() {
        return $this->getObj()->beginTransaction();
    }

    /**
     * 提交事务
     * @return bool
     * @throws \Exception
     */
    public function commit() {
        return $this->getObj()->commit();
    }

    /**
     * 回滚事务
     * @return bool
     * @throws \Exception
     */
    public function rollBack() {
        return $this->getObj()->rollBack();
    }

    /**
     * 获取驱动对象
     * @return model\DbInterface|null
     * @throws \Exception
     */
    public function getObj() {
        $key = 'model_' . http_build_query($this->config);
        if (!\dux\Dux::di()->has($key)) {
            $class = new $this->driver($this->config);
            if (!$class instanceof \dux\kernel\model\DbInterface) {
                throw new \Exception('The database class must interface class inheritance', 500);
            }
            \dux\Dux::di()->set($key, $class);
        }
        return \dux\Dux::di()->get($key);
    }


    /**
     * 拼接表名
     * @param $table
     * @param bool $prefix
     * @return string
     */
    protected function _tableQuote($table, $prefix = true) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/i', $table)) {
            throw new \Exception("Incorrect table name \"$table\"");
        }
        return '`' . ($prefix ? $this->prefix : '') . $table . '`';
    }

    /**
     * 拼接字段
     * @param string $string
     * @return string
     */
    protected function _columnQuote(string $string) {
        if (!preg_match('/^[a-zA-Z0-9_*]+(\.?[a-zA-Z0-9_*]+)?$/i', $string)) {
            throw new InvalidArgumentException("Incorrect column name \"$string\"");
        }
        if (strpos($string, '.') !== false) {
            if (strpos($string, '*') !== false) {
                return '`' . str_replace('.', '`.', $string);
            } else {
                return '`' . str_replace('.', '`.`', $string) . '`';
            }
        }
        return '`' . $string . '`';
    }

    /**
     * 查询字段
     * @param $columns
     * @param $map
     * @return string
     */
    protected function columnPush(&$columns, &$map) {
        $stack = [];
        foreach ($columns as $key => $value) {
            if (!is_int($key) && strpos($key, '_sql') !== false) {
                $tmp = is_array($value) ? $value : [$value];
                foreach ($tmp as $v) {
                    $stack[] = $this->buildRaw($v, $map);
                }
            }
            if (is_array($value)) {
                $stack[] = $this->columnPush($value, $map, false);
            } elseif (!is_int($key) && is_string($value) && strtoupper(substr($key, -5)) === '[SQL]') {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $key, $match);
                $stack[] = $value . ' AS ' . $this->_columnQuote($match['column']);
            } elseif (is_int($key) && is_string($value)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.\*]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);
                if (!empty($match['alias'])) {
                    $stack[] = $this->_columnQuote($match['column']) . ' AS ' . $this->_columnQuote($match['alias']);
                    $columns[$key] = $match['alias'];
                    if (!empty($match['type'])) {
                        $columns[$key] .= '[' . $match['type'] . ']';
                    }
                } else {

                    $stack[] = $this->_columnQuote($match['column']);
                }
            }
        }
        return implode(',', $stack);
    }

    /**
     * 字段类型
     * @param $columns
     * @param $stack
     * @return mixed
     */
    protected function columnMap($columns, &$stack) {
        foreach ($columns as $key => $value) {
            if (!is_int($key) && strpos($key, '_sql') !== false) {
                preg_match('/(?<column>[a-zA-Z0-9_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $keyMatch);
                $stack[$key] = ['_sql', $keyMatch['type'] ?: 'String'];
            } elseif (is_int($key) && strpos($value, '*') === false) {
                preg_match('/([a-zA-Z0-9_\*]+\.)?(?<column>[a-zA-Z0-9_\*]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $keyMatch);
                $columnKey = !empty($keyMatch['alias']) ? $keyMatch['alias'] : $keyMatch['column'];
                if (isset($keyMatch['type'])) {
                    $stack[$value] = [$columnKey, $keyMatch['type']];
                } else {
                    $stack[$value] = [$columnKey, 'String'];
                }
            } elseif (strtoupper(substr($key, -5)) === '[SQL]') {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $keyMatch);
                $columnKey = $keyMatch['column'];
                if (isset($keyMatch['type'])) {
                    $stack[$key] = [$columnKey, $keyMatch['type']];
                } else {
                    $stack[$key] = [$columnKey, 'String'];
                }
            } elseif (!is_int($key) && is_array($value)) {
                $this->columnMap($value, $stack, false);
            }
        }
        return $stack;
    }

    /**
     * 格式化数据
     * @param $data
     * @param $columns
     * @param $columnMap
     * @return array
     */
    protected function dataMap($data, $columns, $columnMap) {
        $result = [];
        $dataKeys = array_column($columnMap, 0);
        foreach ($data as $key => $vo) {
            if (!in_array($key, $dataKeys)) {
                $columnMap[$key] = [$key, 'string'];
                $columns[] = $key;
            }
        }
        foreach ($columns as $key => $vo) {
            if (!is_int($key) && strpos($key, '_sql') !== false) {
                continue;
            }
            if (!is_int($key) && is_array($vo)) {
                //子循环
                $result[$key] = $this->dataMap($data, $vo, $columnMap);
            } else {
                if (is_string($key)) {
                    $map = $columnMap[$key];
                } else {
                    $map = $columnMap[$vo];
                }
                [$columnKey, $columnType] = $map;
                $dataKey = preg_replace("/^[a-zA-Z0-9_]+\./i", "", $columnKey);
                $item = $data[$columnKey];
                if ($columnType) {
                    if (is_null($item)) {
                        $result[$dataKey] = null;
                        continue;
                    }
                    switch (strtoupper($columnType)) {
                        case 'NUMBER':
                            $result[$dataKey] = (double)$item;
                            break;
                        case 'INT':
                            $result[$dataKey] = (int)$item;
                            break;
                        case 'BOLL':
                            $result[$dataKey] = (bool)$item;
                            break;
                        case 'ARRAY':
                        case 'OBJECT':
                            $result[$dataKey] = unserialize($item);
                            break;
                        case 'JSON':
                            $result[$dataKey] = json_decode($item, true);
                            break;
                        case 'STRING':
                        default:
                            $result[$dataKey] = $item;
                            break;
                    }
                } else {
                    $result[$key] = $item;
                }
            }
        }
        return $result;
    }

    /**
     * 获取字段
     * @return array|string
     */
    protected function _getField() {
        $fields = $this->options['field'];
        $this->options['field'] = [];
        $aliasData = $this->fetchTableAlias();
        $fields = $this->columnExpand($fields, $aliasData);
        $filedSql = $this->columnPush($fields, $this->options['map'], true);
        $filedSql = str_replace('{pre}', $this->config['prefix'], $filedSql);
        return ['sql' => $filedSql, 'column' => $fields];
    }

    /**
     * 获取连表
     * @return string
     * @throws \Exception
     */
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
        $tableJoin = [];
        foreach ($join as $vo) {
            [$table, $alias, $relation, $way] = $vo;
            $table = $this->_tableQuote($table) . ($alias ? ' AS ' . $this->_columnQuote($alias) : '');
            if (is_string($relation)) {
                $relation = 'USING ("' . $relation . '")';
            }
            if (is_array($relation)) {
                if (isset($relation[0])) {
                    $relation = [$relation[0] => $relation[1]];
                }
                $joins = [];
                foreach ($relation as $key => $value) {
                    if ($key == '_sql') {
                        $tmpArray = is_array($value) ? $value : [$value];
                        foreach ($tmpArray as $tmp) {
                            $joins[] = $tmp;
                        }
                    } else {
                        $joins[] = (strpos($key, '.') > 0 ? $this->_columnQuote($key) : '"' . $key . '"') .
                            ' = ' .
                            (strpos($value, '.') > 0 ? $this->_columnQuote($value) : '"' . $value . '"');
                    }
                }
                $relation = ' ON ' . implode(' AND ', $joins);
            }
            $tableJoin[] = " {$joinArray[$way]} JOIN {$table} {$relation} ";
        }
        return implode(' ', $tableJoin);
    }

    /**
     * 获取主表
     * @return mixed|string
     * @throws \Exception
     */
    protected function _getTable() {
        $table = $this->options['table'];
        $this->options['table'] = [];
        if (empty($table)) {
            $class = get_called_class();
            $class = str_replace('\\', '/', $class);
            $class = basename($class);
            $class = substr($class, 0, -5);
            $class = preg_replace("/(?=[A-Z])/", "_\$1", $class);
            $class = substr($class, 1);
            $class = strtolower($class);
            $table = $this->_tableQuote($class);
        } else {
            $table = $this->_tableQuote($table[0]) . ($table[1] ? ' AS ' . $this->_columnQuote($table[1]) : '');
        }
        return $table;
    }

    protected function _getLock() {
        $lock = $this->options['lock'];
        $this->options['lock'] = [];
        return $lock;
    }

    protected function _getRaw() {
        $return = $this->options['raw'];
        $this->options['raw'] = false;
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
        return $this->_whereParsing($condition, $this->options['map'], ' AND ');
    }

    protected function _getBindParams() {
        $map = $this->options['map'];
        $this->options['map'] = [];
        return $map;
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


    /**
     * 自动键
     * @return string
     */
    private function mapKey() {
        return ':MeDoO_' . $this->guid++ . '_mEdOo';
    }

    /**
     * 展开所有字段
     * @param $fields
     * @param $aliasData
     * @return array
     */
    private function columnExpand($fields, $aliasData) {
        $data = [];
        foreach ($fields as $key => $vo) {
            if (is_int($key) && !is_array($vo) && strpos($vo, '*') !== false) {
                if (strpos($vo, '.') !== false) {
                    $tmp = explode('.', $vo, 2);
                    $alias = $tmp[0];
                    $table = $aliasData[$alias];
                } else {
                    $alias = $this->options['table'][1];
                    $table = $aliasData[$this->options['table'][1] ?: $this->options['table'][0]];
                }
                $tableFiels = $this->fetchColumnNames($table, $alias);
                foreach ($tableFiels as $field) {
                    $data[] = $field;
                }
            } elseif (!is_int($key) && strpos($key, '_sql') !== false) {
                $data[$key] = $vo;
            } elseif (!is_int($key) && is_array($vo)) {
                $data[$key] = $this->columnExpand($vo, $aliasData);
            } else {
                $data[] = $vo;
            }
        }
        return $data;
    }

    /**
     * 编译Sql
     * @param $value
     * @param $map
     * @return string|string[]|null
     */
    private function buildRaw($value, &$map) {
        $query = preg_replace_callback(
            '/(([`\']).*?)?((FROM|TABLE|INTO|UPDATE|JOIN)\s*)?\<(([a-zA-Z0-9_]+)(\.[a-zA-Z0-9_]+)?)\>(.*?\2)?/i',
            function ($matches) {
                if (!empty($matches[2]) && isset($matches[8])) {
                    return $matches[0];
                }

                if (!empty($matches[4])) {
                    return $matches[1] . $matches[4] . ' ' . $this->_tableQuote($matches[5]);
                }

                return $matches[1] . $this->_columnQuote($matches[5]);
            },
            $value);
        return $query;
    }

    /**
     * 数据类型
     * @param $value
     * @param $type
     * @return array
     */
    private function typeMap($value, $type) {
        $map = [
            'NULL' => PDO::PARAM_NULL,
            'integer' => PDO::PARAM_INT,
            'double' => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'string' => PDO::PARAM_STR,
            'object' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB
        ];

        if ($type === 'boolean') {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = null;
        }
        return [$value, $map[$type]];
    }

    /**
     * 条件链接
     * @param $data
     * @param $map
     * @param $conjunctor
     * @param $outerConjunctor
     * @return string
     */
    protected function _whereConjunct($data, $map, $conjunctor, $outerConjunctor) {
        $stack = [];
        foreach ($data as $value) {
            $stack[] = '(' . $this->_whereParsing($value, $map, $conjunctor) . ')';
        }
        return implode($outerConjunctor . ' ', $stack);
    }

    /**
     * 条件组合
     * @param $data
     * @param $map
     * @param $conjunctor
     * @return string
     */
    private function _whereParsing($data, &$map, $conjunctor) {
        $stack = [];
        foreach ($data as $key => $value) {
            $type = gettype($value);
            //嵌套条件
            if ($type === 'array' && preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)) {
                $relationship = $relation_match[1];
                $stack[] = $value !== array_keys(array_keys($value)) ?
                    '(' . $this->_whereParsing($value, $map, ' ' . $relationship) . ')' :
                    '(' . $this->_whereConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';
                continue;
            }
            $mapKey = $this->mapKey();

            //执行原生条件
            if (strtolower($key) == '_sql') {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach ($value as $s) {
                    $stack[] = $this->buildRaw($s, $map);
                }
            } //普通条件
            else if (is_int($key) && preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)) {
                $stack[] = $this->_columnQuote($match[1]) . ' ' . $match['operator'] . ' ' . $this->_columnQuote($match[3]);
            } //运算符条件
            else {
                preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
                $column = $this->_columnQuote($match[1]);

                if (isset($match['operator'])) {
                    $operator = $match['operator'];
                    if (in_array($operator, ['>', '>=', '<', '<='])) {
                        $condition = $column . ' ' . $operator . ' ';
                        if (is_numeric($value)) {
                            $condition .= $mapKey;
                            $map[$mapKey] = [$value, is_float($value) ? PDO::PARAM_STR : PDO::PARAM_INT];
                        } else {
                            $condition .= $mapKey;
                            $map[$mapKey] = [$value, PDO::PARAM_STR];
                        }
                        $stack[] = $condition;
                    } elseif ($operator === '!') {
                        switch ($type) {
                            case 'NULL':
                                $stack[] = $column . ' IS NOT NULL';
                                break;
                            case 'array':
                                $placeholders = [];

                                foreach ($value as $index => $item) {
                                    $stack_key = $mapKey . $index . '_i';
                                    $placeholders[] = $stack_key;
                                    $map[$stack_key] = $this->typeMap($item, gettype($item));
                                }
                                $stack[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ')';
                                break;
                            case 'integer':
                            case 'double':
                            case 'boolean':
                            case 'string':
                                $stack[] = $column . ' != ' . $mapKey;
                                $map[$mapKey] = $this->typeMap($value, $type);
                                break;
                        }
                    } elseif ($operator === '~' || $operator === '!~') {
                        if ($type !== 'array') {
                            $value = [$value];
                        }
                        $connector = ' OR ';
                        $data = array_values($value);
                        if (is_array($data[0])) {
                            if (isset($value['AND']) || isset($value['OR'])) {
                                $connector = ' ' . array_keys($value)[0] . ' ';
                                $value = $data[0];
                            }
                        }
                        $like_clauses = [];
                        foreach ($value as $index => $item) {
                            $item = strval($item);
                            if (!preg_match('/(\[.+\]|[\*\?\!\%#^-_]|%.+|.+%)/', $item)) {
                                $item = '%' . $item . '%';
                            }
                            $like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $mapKey . 'L' . $index;
                            $map[$mapKey . 'L' . $index] = [$item, PDO::PARAM_STR];
                        }
                        $stack[] = '(' . implode($connector, $like_clauses) . ')';
                    } elseif ($operator === '<>' || $operator === '><') {
                        if ($type === 'array') {
                            if ($operator === '><') {
                                $column .= ' NOT';
                            }
                            $stack[] = '(' . $column . ' BETWEEN ' . $mapKey . 'a AND ' . $mapKey . 'b)';
                            $data_type = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;
                            $map[$mapKey . 'a'] = [$value[0], $data_type];
                            $map[$mapKey . 'b'] = [$value[1], $data_type];
                        }
                    } elseif ($operator === 'REGEXP') {
                        $stack[] = $column . ' REGEXP ' . $mapKey;
                        $map[$mapKey] = [$value, PDO::PARAM_STR];
                    } elseif ($operator === 'SQL') {
                        $stack[] = $column . ' ' . $this->buildRaw($value, $map);
                        //$map[$mapKey] = [$value, PDO::PARAM_STR];
                    }

                } else {
                    switch ($type) {
                        case 'NULL':
                            $stack[] = $column . ' IS NULL';
                            break;
                        case 'array':
                            $placeholders = [];
                            foreach ($value as $index => $item) {
                                $stack_key = $mapKey . $index . '_i';
                                $placeholders[] = $stack_key;
                                $map[$stack_key] = $this->typeMap($item, gettype($item));
                            }
                            $stack[] = $column . ' IN (' . implode(', ', $placeholders) . ')';
                            break;
                        case 'integer':
                        case 'double':
                        case 'boolean':
                        case 'string':
                            $stack[] = $column . ' = ' . $mapKey;
                            $map[$mapKey] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
        }
        return implode($conjunctor . ' ', $stack);
    }

    private function fetchColumnNames($table, $alias = '') {
        $columns = $this->getObj()->getLink()->query('SHOW columns FROM ' . $this->_tableQuote($table))->fetchAll(PDO::FETCH_COLUMN);
        $columns = array_map(function ($val) use ($alias) {
            return $alias ? $alias . '.' . $val : $val;
        }, $columns);
        return $columns;
    }

    private function fetchTableAlias() {
        $data = [];
        foreach ($this->options['join'] as $vo) {
            [$table, $alias, $relation, $way] = $vo;
            $data[$alias ?: $table] = $table;
        }
        $data[$this->options['table'][1] ?: $this->options['table'][0]] = $this->options['table'][0];
        return $data;
    }


}
