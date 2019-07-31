<?php

/**
 * mongo底层驱动
 * @author: TS
 */
namespace dux\kernel\modelNo;

class MongoDbDriver {
    private $_manager;
    private $_host;
    private $_port;
    private $_username;
    private $_password;
    private $_dbuser;
    private $_db;
    private $_options;
    private $_driverOptions;

    private $_wtimeout = 1000;

    /**
     * 默认主键名称
     * @var string
     */
    private $primaryDefault = '_id';

    private $primary = '';

    public function __construct($param = array()){
        if(!empty($param['host'])){
            $this->_host = $param['host'] ? $param['host'] . ':'  : '';
            $this->_port = $param['port'] ? $param['port'] : '';
            $this->_username = $param['username'] ? $param['username'] . ':' : '';
            $this->_password = $param['password'] ? $param['password'] : '';
            $this->_db = $param['dbname'] ? $param['dbname'] : '';

            $this->_dbuser = !empty($param['dbuser']) ? $param['dbuser'] : $param['dbuser'];

            $this->_options = !empty($param['options']) ? $param['options'] : [];
            $this->_driverOptions = !empty($param['driverOptions']) ? $param['driverOptions'] : [];

            if(!empty($param['wtimeout']))
                $this->_wtimeout = $param['wtimeout'];

            $mongo = "mongodb://" . $this->_username . $this->_password . '@' . $this->_host . $this->_port . '/' . $this->_dbuser;
        }else{
            return false;
        }
        $this->_manager = new \MongoDB\Driver\Manager($mongo,$this->_options,$this->_driverOptions);
    }

    public function getInstense(){
        return $this->_manager;
    }
    public function getDB(){
        return $this->_db;
    }
    public function getBulk(){
        return new \MongoDB\Driver\BulkWrite;
    }
    public function getWriteConcern(){
        new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY, $this->_wtimeout);
    }

    /**
     * 设置主键名称
     * @param $pri
     * @return $this
     */
    public function setPrimary($pri){
        $this->primary = $pri;
        return $this;
    }

    /**
     * 获取主键名称
     * @return string
     */
    public function getPrimary(){

        return !empty($this->primary) ? $this->primary : $this->primaryDefault;
    }

    /**
     * 插入数据
     * @param $collection 集合名
     * @param array $document 数据
     * @return array|bool
     */
    public function insert($collection,array $document){
        $bulk = $this->getBulk();
        $write_concern = $this->getWriteConcern();

        $ids = [];

        foreach ($document as $val){

            if(!isset($val[$this->primaryDefault])){
                $val[$this->primaryDefault] = new \MongoDB\BSON\ObjectID;
            }

            $id = is_object($val[$this->primaryDefault]) ? (string)$val[$this->primaryDefault] : $val[$this->primaryDefault];

            $ids[] = $id;

            $bulk->insert($val);
        }

        $writeResult = $this->_manager->executeBulkWrite($this->_db. '.' .$collection, $bulk, $write_concern);

        if(!empty($writeResult->getWriteErrors()))
            return false;

        return $ids;
    }

    /**
     * 删除数据
     * @param string $collection
     * @param array $where
     * @param array $option
     * @return mixed
     */
    public function delete($collection, $where = array(), $option = array()){
        $bulk = $this->getBulk();
        $bulk->delete($where, $option);

        $writeResult = $this->_manager->executeBulkWrite($this->_db. '.' .$collection, $bulk);

        if(empty($writeResult->getWriteErrors())){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新数据
     * @param array $where 类似where条件
     * @param array $field  要更新的字段
     * @param bool $upsert 如果不存在是否插入，默认为false不插入
     * @param bool $multi 是否更新全量，默认为false
     * @param string $collection 集合
     * @return mixed
     */
    public function update($collection, $where = array(), $field = array(), $upsert = false, $multi = false){
        if(empty($where)){
            return 'filter is null';
        }

        $bulk = $this->getBulk();
        $write_concern = $this->getWriteConcern();

        $updateOptions = [
            'upsert'    => $upsert,
            'multi'     => $multi
        ];

        $bulk->update($where, $field, $updateOptions);
        $res = $this->_manager->executeBulkWrite($this->_db. '.' .$collection, $bulk, $write_concern);
        if(empty($res->getWriteErrors())){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询
     * @param $collection
     * @param $filter
     * @param $options
     * @return array
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function query($collection, $filter, $options){
        $query = new \MongoDB\Driver\Query($filter, $options);
        $res = $this->_manager->executeQuery($this->_db. '.' .$collection, $query);
        $data = array();
        foreach ($res as $item){
            $data[] = $this->objToArray($item);
        }
        return $data;
    }

    /**
     * 执行MongoDB命令
     * @param array $param
     * @return \MongoDB\Driver\Cursor
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function command(array $param)
    {
        $cmd = new \MongoDB\Driver\Command($param);
        return $this->_manager->executeCommand($this->_db, $cmd);
    }

    /**
     * 按条件计算个数
     * @param string $collName 集合名
     * @param array $where 条件
     * @return int
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function count($collName, array $where)
    {
        $result = 0;
        $cmd = [
            'count' => $collName,
            'query' => $where
        ];
        $arr = $this->command($cmd)->toArray();
        if (!empty($arr)) {
            $result = $arr[0]->n;
        }
        return $result;
    }


    /**
     * 聚合查询
     * @param $collName
     * @param array $where
     * @param array $group
     * @return mixed
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function aggregate($collName, array $where, array $group){
        $cmd = [
            'aggregate' => $collName,
            'pipeline' => [
                ['$match' => $where],
                ['$group' => $group]
            ],
            'cursor' => new \stdClass,
        ];

        $result = $this->command($cmd)->toArray();

        $returnData = null;

        if(isset($result[0]->result))
            $returnData = $result[0]->result;
        else
            $returnData = (array)$result[0];

        return $returnData;
    }

    /**
     * 同mysql中的distinct功能
     *
     * @param string $collName collection名
     * @param string $key 要进行distinct的字段名
     * @param array $where 条件
     * @return array
     * Array
     * (
     * [0] => 1.0
     * [1] => 1.1
     * )
     */
    public function distinct($collName, $key, array $where){
        $result = [];
        $cmd = [
            'distinct' => $collName,
            'key' => $key,
            'query' => $where
        ];
        $arr = $this->command($cmd)->toArray();
        if (!empty($arr)) {
            $result = $arr[0]->values;
        }
        return $result;
    }

    private function objToArray($data){

        if(empty($data))
            return [];

        $tmp = (array)$data;
        //主键转换
        if(isset($tmp[$this->primaryDefault]))
            $tmp[$this->getPrimary()] = is_object($tmp[$this->primaryDefault]) ? (string)$tmp[$this->primaryDefault] : $tmp[$this->primaryDefault];

        if($this->primaryDefault != $this->getPrimary())
            unset($tmp[$this->primaryDefault]);

        return $tmp;
    }

}