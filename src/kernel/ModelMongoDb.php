<?php

/**
 * (mongo处理) 通用模块 基础层
 * @author: TS
 */
namespace dux\kernel;

/**
 * 抽象类
 * Class ToolsMongoDbBaseModel
 * @package app\tools\model
 */
abstract class ModelMongoDb {

    protected $config = [];
    protected $database = 'default';

    /**
     * 默认主键名称
     * @var string
     */
    protected $primaryDefault = '_id';

    protected $options = array(
        'table' => '',
        'field' => null,
        'where' => [],
        'data' => [],
        'order' => '',
        'limit' => ''
    );

    protected $prefix = '';

    protected static $objArr = [];

    public function __construct($database = '') {
        if ($database) {
            $this->database = $database;
        }
        $config = \dux\Config::get('dux.dataMongoDb');
        $this->config = $config[$this->database];
        if (empty($this->config) || empty($this->config['type'])) {
            throw new \Exception($this->config['type'] . ' database config error', 500);
        }

        $this->prefix = empty($this->config['prefix']) ? '' : $this->config['prefix'];
    }

    /**
     * 默认字段
     * @var null
     */
    protected $defaultFields = null;

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
        $data = $this->_select($this->_getTable() , $this->_getWhere(), $this->_getField(), $this->_getOrder(), $this->_getLimit());

        if($data === false)
            return false;

        return empty($data) ? [] : $data;
    }

    public function count() {
        $count = $this->_count($this->_getTable() , $this->_getWhere());

        if($count === false)
            return false;

        return $count;
    }

    public function find() {
        $data = $this->limit(1)->select();

        if($data === false)
            return false;

        return isset($data[0]) ? $data[0] : [];
    }

    /**
     * 添加数据
     * @param array $data
     * @return bool|mixed
     */
    public function insert($data = []) {

        if(!empty($data))
            $this->data($data);

        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }

        $id = $this->_insert($this->_getTable(), [ $this->_getData() ]);

        if($id === false)
            return false;

        return $id[0];
    }


    /**
     * 批量添加数据
     * @param array $data
     * @return array|bool
     */
    public function insertAll($data = []){

        if(!empty($data))
            $this->data($data);

        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return false;
        }

        $ids = $this->_insert($this->_getTable(),$this->_getData());

        if($ids === false)
            return false;

        return $ids;
    }

    /**
     * 更新数据
     * @return bool|int
     */
    public function update() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return $this->error('没有where条件!');
        }

        if (empty($this->options['data']) || !is_array($this->options['data'])) {
            return $this->error('没有要更新的数据!');
        }

        $status = $this->_update($this->_getTable(), $this->_getWhere(),$this->_getData(false));
        return ($status === false) ? false : $status;
    }


    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }

        $status = $this->_delete($this->_getTable(), $this->_getWhere());
        return ($status === false) ? false : $status;
    }

    /**
     * 查询
     * @param $table
     * @param $where
     * @param $options
     * @return bool
     */
    public function query($table, $where, $options){

        $db = $this->db();

        if(!$db)
            return false;

        if($table === false)
            return false;

        return $db->query($table,$where,$options);
    }

    /**
     * 聚合查询
     * @param $table
     * @param $where
     * @param $group
     * @return bool
     */
    public function aggregate($table, $where, $group){

        $db = $this->db();

        if(!$db)
            return false;

        if($table === false)
            return false;

        return $db->aggregate($table,$where,$group);
    }

    /**
     * 同mysql中的distinct功能
     * @param string $table
     * @param string $key 要进行distinct的字段名
     * @param array $where 条件
     * @return bool
     */
    public function distinct($table, $key, $where){

        $db = $this->db();

        if(!$db)
            return false;

        if($table === false)
            return false;

        return $db->distinct($table,$key,$where);
    }

    public function sum($field) {
        return $this->_sum($this->_getTable(),$this->_getWhere(),$field);
    }

    public function getFields() {
        return $this->defaultFields();
    }


    protected function _getField() {
        $fields = $this->options['field'];
        $this->options['field'] = [];

        $fieldsList = [];

        if(!empty($fields)){

            $defaultFields = $this->defaultFields();

            $fieldsList = [];
            foreach ($fields as $vo) {

                if(!in_array($vo,$defaultFields))
                    continue;

                $fieldsList[] = $vo;
            }
        }

        $fieldsList = $this->_fieldParsing($fieldsList);

        return $fieldsList;
    }

    /**
     * 获取表名
     * @return bool
     */
    protected function _getTable() {
        $table = $this->options['table'];
        $this->options['table'] = '';
        if (empty($table)) {

            if(!empty($this->table)){
                $table = $this->table;
            }else{
                $class = get_called_class();
                $class = str_replace('\\', '/', $class);
                $class = basename($class);
                $class = substr($class, 0, -5);
                $class = preg_replace("/(?=[A-Z])/", "_\$1", $class);
                $class = substr($class, 1);
                $class = strtolower($class);
                $table = $class;
            }

        } else {
            preg_match('/([a-zA-Z0-9_\-]*)\s?(\(([a-zA-Z0-9_\-]*)\))?/', $table, $match);
            $table = trim($match[1]) . (isset($match[3]) ? ' as ' . $match[3] : '');
        }
        $table = $this->prefix . $table;
        return $table;
    }

    /**
     * 获取where条件
     * @return array|bool|mixed|null
     */
    protected function _getWhere() {
        $condition = $this->options['where'];
        $this->options['where'] = [];

        $condition = $this->_whereParsing($condition);

        return $condition;
    }


    /**
     * 获取data数据
     * @return array
     */
    protected function _getData() {

        $data = $this->options['data'];
        $this->options['data'] = [];

        return $data;
    }

    protected function _getOrder() {
        $order = $this->options['order'];
        $this->options['order'] = '';

        $order = $this->_orderParsing($order);

        return $order;
    }

    protected function _getLimit() {
        $limit = $this->options['limit'];
        $this->options['limit'] = [];
        if (empty($limit)) {
            return 0;
        }

        if(!is_array($limit))
            $limit = explode(',',$limit);

        if(count($limit) == 1)
            $limitArr = [0,(int)$limit[0]];
        else
            $limitArr = [(int)$limit[0],(int)$limit[1]];

        return $limitArr;
    }

    /**
     * 通过条件查询
     * @param $table
     * @param $where
     * @param array $fields
     * @param string $order
     * @param int $limit
     * @return array|bool
     */
    private function _select($table,$where,$fields = [],$order = '',$limit = 0){

        if($table === false)
            return false;

        $db = $this->db();

        if($db === false)
            return false;

        $options = [];

        if(!empty($fields))
            $options['projection'] = $fields;

        if(!empty($order))
            $options['sort'] = $order;

        if(!empty($limit)){
            $options['skip'] = $limit[0];
            $options['limit'] = $limit[1];
        }

        return $db->query($table,$where,$options);
    }


    private function _count($table,$where){

        if($table === false)
            return false;

        $db = $this->db();

        if(!$db)
            return false;

        return $db->count($table,$where);
    }

    /**
     * 添加处理
     * @param $table
     * @param array $dataList 添加数组
     * @return bool | array
     */
    private function _insert($table,array $dataList){
        $db = $this->db();
        if (!$db)
            return false;

        if (empty($table))
            return $this->error('数据集字段未配置!');

        if (empty($dataList) || count($dataList) == 0)
            return $this->error('没有需要添加的数据!');

        if (!isset($dataList[0]))
            return $this->error('数据格式错误!');

        //添加默认值
        foreach ($dataList as &$data)
            $data = $this->_dataParsing($data);

        return $db->insert($table,$dataList);
    }

    /**
     * 更新处理
     * @param $table
     * @param $where
     * @param $data
     * @return bool|int
     */
    private function _update($table,$where,$data){

        $db = $this->db();

        if(!$db)
            return false;

        if($table === false)
            return false;

        if(empty($data))
            return $this->error('没有要更新的数据!');

        $data = $this->_dataParsing($data,false);

        return $db->update($table,$where,['$set' => $data]);
    }


    /**
     * 删除处理
     * @param $table
     * @param $where
     * @return bool
     */
    private function _delete($table,$where){

        if($table === false)
            return false;

        $db = $this->db();

        if(!$db)
            return false;

        return $db->delete($table,$where);
    }

    /**
     * 求和
     * @param $table
     * @param $where
     * @param $field
     * @return bool
     */
    private function _sum($table,$where,$field){

        $db = $this->db();

        if(!$db)
            return false;

        if($table === false)
            return false;

        $group = [
            $this->primaryDefault => null,
            'result'   => [
                '$sum' => '$' . $field
            ]
        ];

        return $db->aggregate($table,$where,$group);
    }

    /* 方法定义 */

    /**
     * 获取db连接
     * @return mixed
     */
    public function db(){

        if (empty(self::$objArr[$this->database])) {
            self::$objArr[$this->database] = new \dux\kernel\modelNo\MongoDbDriver($this->config);
        }

        $obj = self::$objArr[$this->database];

        if(method_exists($obj, 'setPrimary'))
            $obj = $obj->setPrimary($this->getPrimary());

        return $obj;
    }

    /**
     * 获取主键
     * @return string
     */
    abstract public function getPrimary();

    /**
     * 失败返回
     * @param $msg
     * @return bool
     */
    abstract protected function error($msg);

    /**
     * 成功返回
     * @param bool $data
     * @return bool
     */
    abstract protected function success($data);

    /*############################# 字段操作start #############################*/

    /**
     * 默认字段
     * @return array|null
     */
    protected function defaultFields(){

        if(!is_null($this->defaultFields))
            return $this->defaultFields;

        $this->defaultFields = array_keys($this->paramDefaultFields());

        array_unshift($this->defaultFields,$this->getPrimary());

        return $this->defaultFields;
    }

    /**
     * 字段类型
     * @return array
     */
    abstract public function paramTypeFields();

    /**
     * 字段默认值
     * @return array
     */
    abstract public function paramDefaultFields();


    /*############################# 字段操作end #############################*/


    /**
     * 解析where条件
     * @param $whereData
     * @return array
     */
    protected function whereParsing($whereData){
        return $this->_whereParsing($whereData);
    }

    /**
     * 解析where条件
     * @param $whereData
     * @return array
     */
    private function _whereParsing($whereData){

        $placeArr = [
            'OR'        => '$or',
            '>'         => '$gt',
            '>='        => '$gte',
            '<'         => '$lt',
            '<='        => '$lte',
            '!'         => '$ne',
            'in'        => '$in',
            '!in'       => '$nin'
        ];

        $whereList = [];

        //获取值 (兼容弱类型变量)
        $getVal = function ($field,$value){

            if(!is_array($value))
                return $this->_dataParsing([$field => $value],false)[$field];

            $list = [];

            foreach ($value as $val)
                $list[] = $this->_dataParsing([$field => $val],false)[$field];

            return $list;
        };

        foreach ($whereData as $key => $value) {

            if(is_object($value) && is_callable($value)){
                $value = $value();
            }

            if (is_array($value) && preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)) {
                $relationship = $relation_match[1];

                $whereVal = $this->_whereParsing($value);

                if($relationship == 'OR'){

                    foreach ($whereVal as $whereKey => $whereValue){
                        $whereList[$placeArr[$relationship]][] = [$whereKey => $whereValue];
                    }
                }
                else
                    $whereList = array_merge($whereList,$whereVal);
                continue;
            }

            preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
            $key = str_replace('`', '', $match[1]);

            if($key == $this->getPrimary() && $this->getPrimary() != $this->primaryDefault)
                $key = $this->primaryDefault; //主键字段转换

            if (isset($match['operator'])) {
                $operator = $match['operator'];

                //范围公式
                if (in_array($operator, ['>', '>=', '<', '<='])) {

                    $whereList[$key][$placeArr[$operator]] = $getVal($key,$value);

                }elseif ($operator === '!') {
                    $whereList[$key] = is_array($value) ? [$placeArr['!in'] => $getVal($key,$value)] : [$placeArr['!'] => $getVal($key,$value)];
                }elseif ($operator === '~' || $operator === '!~') {

                    $valList = [];

                    if(is_array($value)){
                        foreach ($value as $val)
                            $valList[] = new \MongoDB\BSON\Regex($getVal($key,$val),'i');
                    }else{
                        $valList[] = new \MongoDB\BSON\Regex($getVal($key,$value),'i');
                    }

                    $whereList[$key] = [
                        ($operator == '~' ? $placeArr['in'] : $placeArr['!in'])  => $valList
                    ];
                }

            }else{
                $whereList[$key] = is_array($value) ? [$placeArr['in'] => $getVal($key,$value)] : $getVal($key,$value);
            }

        }

        return $whereList;
    }



    /**
     * order 解析
     * @param $order
     * @return array
     */
    private function _orderParsing($order){

        if(empty($order))
            return [];

        //解析order by
        $orderByArr = explode(',',$order);

        $list = [];

        foreach ($orderByArr as $k=>$v){

            //false 升序 true 降序
            $sortOrder = false;

            $fieldOrder = null;

            if(strrpos($v,' desc')!==false){
                $sortOrder = true; //降序
                $fieldOrder = str_replace(' desc','',$v);
            }else{
                $fieldOrder = str_replace(' asc','',$v);
            }

            $fieldOrder = trim($fieldOrder);

            $list[$fieldOrder] = $sortOrder ? -1 : 1;
        }

        return $list;
    }

    /**
     * 字段解析
     * @param $fields
     * @return array
     */
    private function _fieldParsing($fields){

        if(empty($fields))
            return [];

        if(!is_array($fields))
            $fields = explode(',',$fields);

        $list = [];

        if(isset($fields[0]) && $fields[0] == '*')
            return [];

        //1 包含 0 不包含
        foreach ($fields as $k=>$v)
            $list[$v] = 1;

        if(!in_array($this->getPrimary(),$fields))
            $list[$this->getPrimary()] = 0;

        return $list;
    }


    /**
     * 获取可用data数据
     * @param array $data
     * @param bool $dataDefault 是否叠加默认值
     * @return array
     */
    private function _dataParsing($data = [],$dataDefault = true) {

        if($dataDefault){
            //默认值
            $dataDefault = $this->paramDefaultFields();

            $data = array_merge($dataDefault,$data);
        }

        //字段类型
        $type = $this->paramTypeFields();

        if(!isset($type[$this->getPrimary()])){
            $type[$this->getPrimary()] = function ($v = null){
                return new \MongoDB\BSON\ObjectId($v);
            };
        }

        //默认id
        $type[$this->primaryDefault] = $type[$this->getPrimary()];

        //主键处理
        if($this->getPrimary() != $this->primaryDefault && isset($data[$this->getPrimary()])){
            $data[$this->primaryDefault] = $data[$this->getPrimary()];
            unset($data[$this->getPrimary()]);
        }

        foreach ($data as $key=>&$v){

            $fieldType = 'string';

            if(isset($type[$key]))
                $fieldType = $type[$key];

            if(is_object($fieldType) && is_callable($fieldType))
                $v = $fieldType($v);
            else
                settype($v,$fieldType);
        }

        if($dataDefault && !isset($data[$this->primaryDefault]))
            $data[$this->primaryDefault] = $type[$this->primaryDefault]();

        return $data;
    }

}