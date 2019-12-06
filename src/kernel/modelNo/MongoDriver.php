<?php

/**
 * mongo底层驱动
 * @author: TS
 */

namespace dux\kernel\modelNo;

class MongoDriver implements \dux\kernel\modelNo\ModelNoInterface {

    protected $config = [];
    protected $link = null;
    protected $bulk = null;
    protected $write = null;
    protected $primary = null;
    protected $primaryDefault = '_id';
    protected $fields = [];
    protected $params = [];


    public function __construct($config = []) {
        $this->config = $config;
    }

    public function setPrimary($pri) {
        $this->primary = $pri;
        return $this;
    }

    public function setFields(array $params = []){
        $this->fields = $params;
        array_unshift($this->fields, $this->getPrimary());
        array_unshift($this->fields, $this->primaryDefault);
        return $this->fields;
    }

    /**
     * 设置参数
     * @param array $params
     * @return $this
     */
    public function setParams(array $params) {
        $this->params = $params;
        return $this;
    }

    public function getPrimary() {
        return !empty($this->primary) ? $this->primary : $this->primaryDefault;
    }

    public function getFields() {
        return $this->fields;
    }

    public function query(string $table, $filter, array $options) {
        $query = new \MongoDB\Driver\Query($filter, $options);
        $res = $this->getLink()->executeQuery($this->config['dbname'] . '.' . $table, $query);
        $data = [];
        foreach ($res as $item) {
            $data[] = $this->objToArray($item);
        }
        return $data;
    }

    public function command(array $param) {
        $cmd = new \MongoDB\Driver\Command($param);
        return $this->getLink()->executeCommand($this->config['dbname'], $cmd);
    }

    public function select(string $table, array $where, ?array $fields = [], ?string $order = '', ?array $limit = []) {
        $fields = $this->parsingField($fields);
        $order = $this->_parsingOrder($order);
        $where = $this->parsingWhere($where);
        $options = [];
        if (!empty($fields)) {
            $options['projection'] = $fields;
        }
        if (!empty($order)) {
            $options['sort'] = $order;
        }
        if (!empty($limit)) {
            $options['skip'] = $limit[0];
            $options['limit'] = $limit[1];
        }
        return $this->query($table, $where, $options);
    }

    public function aggregate(string $table, array $where, $group) {
        $where = $this->parsingWhere($where);
        $cmd = [
            'aggregate' => $table,
            'pipeline' => [
                ['$match' => $where],
                ['$group' => $group]
            ],
            'cursor' => new \stdClass,
        ];
        $result = $this->command($cmd)->toArray();

        $returnData = null;

        if (isset($result[0]->result)) {
            $returnData = $result[0]->result;
        } else {
            $returnData = (array)$result[0];
        }
        return $returnData;
    }

    public function sum(string $table, array $where, string $field) {
        $group = [
            $this->primaryDefault => null,
            'result' => [
                '$sum' => '$' . $field
            ]
        ];
        return $this->aggregate($table, $where, $group);
    }

    public function distinct(string $table, array $where, string $key) {
        /**
         * Array
         * (
         * [0] => 1.0
         * [1] => 1.1
         * )
         */
        $where = $this->parsingWhere($where);
        $result = [];
        $cmd = [
            'distinct' => $table,
            'key' => $key,
        ];
        if ($where) {
            $cmd['query'] = $where;
        }
        $arr = $this->command($cmd)->toArray();
        if (!empty($arr)) {
            $result = $arr[0]->values;
        }
        return $result;
    }

    public function count(string $table, array $where) {
        $where = $this->parsingWhere($where);
        $result = 0;
        $cmd = [
            'count' => $table,
            'query' => $where
        ];
        $arr = $this->command($cmd)->toArray();
        if (!empty($arr)) {
            $result = $arr[0]->n;
        }
        return $result;
    }

    /**
     * 插入数据
     * @param $table 集合名
     * @param array $data 数据
     * @return array|bool
     */
    public function insert(string $table, array $data) {
        $bulk = $this->getBulk();
        $write_concern = $this->getWrite();
        $ids = [];
        foreach ($data as $val) {
            $val = $this->_dataParsing($val);
            if (!isset($val[$this->primaryDefault])) {
                $val[$this->primaryDefault] = new \MongoDB\BSON\ObjectID;
            }
            $id = is_object($val[$this->primaryDefault]) ? (string)$val[$this->primaryDefault] : $val[$this->primaryDefault];
            $ids[] = $id;
            $bulk->insert($val);
        }
        $writeResult = $this->getLink()->executeBulkWrite($this->config['dbname'] . '.' . $table, $bulk, $write_concern);
        if (!empty($writeResult->getWriteErrors())) {
            return false;
        }
        return $ids;
    }

    public function update(string $table, array $where = [], array $data = [], bool $upsert = false, bool $multi = true) {
        return $this->_update($table, $where, '$set',$data, $upsert, $multi);
    }

    public function setInc(string $table, array $where = [], string $field = '', int $num = 1) {
        return $this->_update($table, $where, '$inc',[$field => $num]);
    }

    public function setDec(string $table, array $where = [], string $field = '', int $num = 1) {
        $num *= -1;
        return $this->_update($table, $where,'$inc',[$field => $num]);
    }

    private function _update(string $table, array $where = [],?string $dataSymbol = null, array $data = [], bool $upsert = false, bool $multi = true) {
        $bulk = $this->getBulk();
        $writeConcern = $this->getWrite();
        $updateOptions = [
            'upsert' => $upsert,
            'multi' => $multi
        ];

        $data = $this->_dataParsing($data, false);

        if(!is_null($dataSymbol)){
            $data = [
                $dataSymbol => $data
            ];
        }

        $where = $this->parsingWhere($where);
        $bulk->update($where, $data, $updateOptions);
        $res = $this->getLink()->executeBulkWrite($this->config['dbname'] . '.' . $table, $bulk, $writeConcern);
        if (empty($res->getWriteErrors())) {
            return true;
        } else {
            return false;
        }
    }

    public function delete(string $table, array $where = [], array $option = []) {
        $bulk = $this->getBulk();
        $where = $this->parsingWhere($where);
        $bulk->delete($where, $option);
        $writeResult = $this->getLink()->executeBulkWrite($this->config['dbname'] . '.' . $table, $bulk);
        if (empty($writeResult->getWriteErrors())) {
            return true;
        } else {
            return false;
        }
    }

    private function _dataParsing(array $data = [], bool $dataDefault = true) {
        if (empty($data)) {
            return [];
        }
        $defaultFields = [];
        $typeFields = [];
        $params = $this->params;
        foreach ($params as $key => $vo) {
            $defaultFields[$key] = $vo['default'];
            $typeFields[$key] = $vo['type'];
        }
        if ($dataDefault) {
            $data = array_merge($defaultFields, $data);
        }
        $type = $typeFields;
        if (!isset($type[$this->getPrimary()])) {
            $type[$this->getPrimary()] = function ($v = null) {
                return new \MongoDB\BSON\ObjectId($v);
            };
        }
        $type[$this->primaryDefault] = $type[$this->getPrimary()];
        if ($this->getPrimary() != $this->primaryDefault && isset($data[$this->getPrimary()])) {
            $data[$this->primaryDefault] = $data[$this->getPrimary()];
            unset($data[$this->getPrimary()]);
        }
        foreach ($data as $key => &$v) {
            $fieldType = 'string';
            if (isset($type[$key])) {
                $fieldType = $type[$key];
            }
            if (is_object($fieldType) && is_callable($fieldType)) {
                $v = $fieldType($v);
            } else {
                settype($v, $fieldType);
            }
        }
        if ($dataDefault && !isset($data[$this->primaryDefault])) {
            $data[$this->primaryDefault] = $type[$this->primaryDefault]();
        }
        return $data;
    }

    public function getLink() {
        if (!$this->link) {
            $this->link = $this->_connect();
        }
        return $this->link;
    }

    protected function getBulk() {
        return new \MongoDB\Driver\BulkWrite;
    }

    protected function getWrite() {
        return new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, $this->config['wtimeout']);
    }

    protected function _connect() {
        $mongo = "mongodb://";
        if ($this->config['username'] || $this->config['password']) {
            $mongo = $this->config['username'] . ':' . $this->config['password'] . '@';
        }
        $mongo .= $this->config['host'] . ':' . $this->config['port'] . '/' . $this->config['dbname'];
        return new \MongoDB\Driver\Manager($mongo, (array)$this->config['options']);
    }

    private function objToArray($data) {
        if (empty($data)) {
            return [];
        }
        $tmp = (array)$data;
        if (isset($tmp[$this->primaryDefault])) {
            $tmp[$this->getPrimary()] = is_object($tmp[$this->primaryDefault]) ? (string)$tmp[$this->primaryDefault] : $tmp[$this->primaryDefault];
        }
        if ($this->primaryDefault != $this->getPrimary()) {
            unset($tmp[$this->primaryDefault]);
        }
        return $tmp;
    }

    protected function parsingField($fields) {
        $fieldsList = [];
        if (!empty($fields)) {
            $defaultFields = $this->getFields();
            $fieldsList = [];
            foreach ($fields as $vo) {
                if (!in_array($vo, $defaultFields)) {
                    continue;
                }
                //字段转换
                if ($vo == $this->getPrimary() && $this->getPrimary() != $this->primaryDefault) {
                    $vo = $this->primaryDefault;
                }
                $fieldsList[] = $vo;
            }
        }
        if (empty($fieldsList)) {
            return [];
        }
        if (!is_array($fieldsList)) {
            $fieldsList = explode(',', $fieldsList);
        }
        $list = [];
        if (isset($fieldsList[0]) && $fieldsList[0] == '*') {
            return [];
        }
        foreach ($fieldsList as $k => $v) {
            $list[$v] = 1;
        }
        if (!in_array($this->primaryDefault, $fieldsList)) {
            $list[$this->primaryDefault] = 0;
        }
        return $list;
    }

    protected function parsingWhere($where) {
        if (empty($where)) {
            return [];
        }
        $placeArr = [
            'OR' => '$or',
            '>' => '$gt',
            '>=' => '$gte',
            '<' => '$lt',
            '<=' => '$lte',
            '!' => '$ne',
            'in' => '$in',
            '!in' => '$nin'
        ];
        $whereList = [];

        $getVal = function ($field, $value) {
            if (!is_array($value)) {
                return $this->_dataParsing([$field => $value], false)[$field];
            }
            $list = [];
            foreach ($value as $val) {
                $list[] = $this->_dataParsing([$field => $val], false)[$field];
            }
            return $list;
        };

        foreach ($where as $key => $value) {
            if (is_object($value) && is_callable($value)) {
                $value = $value();
            }
            if (is_array($value) && preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)) {
                $relationship = $relation_match[1];
                $whereVal = $this->parsingWhere($value);
                if ($relationship == 'OR') {
                    foreach ($whereVal as $whereKey => $whereValue) {
                        $whereList[$placeArr[$relationship]][] = [$whereKey => $whereValue];
                    }
                } else {
                    $whereList = array_merge($whereList, $whereVal);
                }
                continue;
            }
            preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
            $key = str_replace('`', '', $match[1]);
            if ($key == $this->getPrimary() && $this->getPrimary() != $this->primaryDefault) {
                $key = $this->primaryDefault;
            }
            if (isset($match['operator'])) {
                $operator = $match['operator'];
                if (in_array($operator, ['>', '>=', '<', '<='])) {
                    $whereList[$key][$placeArr[$operator]] = $getVal($key, $value);
                } elseif ($operator === '!') {
                    $whereList[$key] = is_array($value) ? [$placeArr['!in'] => $getVal($key, $value)] : [$placeArr['!'] => $getVal($key, $value)];
                } elseif ($operator === '~' || $operator === '!~') {
                    $valList = [];
                    if (is_array($value)) {
                        foreach ($value as $val)
                            $valList[] = new \MongoDB\BSON\Regex($getVal($key, $val), 'i');
                    } else {
                        $valList[] = new \MongoDB\BSON\Regex($getVal($key, $value), 'i');
                    }
                    $whereList[$key] = [
                        ($operator == '~' ? $placeArr['in'] : $placeArr['!in']) => $valList
                    ];
                }
            } else {
                $whereList[$key] = is_array($value) ? [$placeArr['in'] => $getVal($key, $value)] : $getVal($key, $value);
            }
        }
        return $whereList;
    }

    protected function _parsingOrder($order) {
        if (empty($order)) {
            return [];
        }
        $orderByArr = explode(',', $order);
        $list = [];
        foreach ($orderByArr as $k => $v) {
            //false 升序 true 降序
            $sortOrder = false;
            $fieldOrder = null;
            if (strrpos($v, ' desc') !== false) {
                $sortOrder = true; //降序
                $fieldOrder = str_replace(' desc', '', $v);
            } else {
                $fieldOrder = str_replace(' asc', '', $v);
            }
            $fieldOrder = trim($fieldOrder);
            //字段转换
            if ($fieldOrder == $this->getPrimary() && $this->getPrimary() != $this->primaryDefault) {
                $fieldOrder = $this->primaryDefault;
            }
            $list[$fieldOrder] = $sortOrder ? -1 : 1;
        }
        return $list;
    }

    public function __destruct() {
        if ($this->link) {
            $this->link = null;
        }
        if ($this->write) {
            $this->write = null;
        }
        if ($this->bulk) {
            $this->bulk = null;
        }
    }


}