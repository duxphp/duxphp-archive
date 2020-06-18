<?php

/**
 * 公共Nosql模型
 */

namespace dux\kernel;

class ModelNo {

    protected $driver = null;
    protected $object = null;
    protected $config = [];

    protected $primary = null;

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

    /**
     * 初始化模型
     * @param string $driver
     * @param array $config
     * @throws \Exception
     */
    public function __construct(string $driver = '', array $config = []) {
        $this->driver = $driver ?: $this->driver;
        if (!class_exists($this->driver)) {
            throw new \Exception('The ModelNo driver class does not exist', 500);
        }
        $this->config = $config ?: $this->config;
        $this->prefix = $this->config['prefix'];
        if (empty($this->config)) {
            throw new \Exception($this->driver . ' ModelNo config error', 500);
        }
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
     * 查询表
     * @param string $table
     * @return $this
     */
    public function table(string $table) {
        $this->options['table'] = $table;
        return $this;
    }

    /**
     * 查询字段
     * @param array $field
     * @return $this
     */
    public function field(array $field) {
        $this->options['field'] = $field;
        return $this;
    }

    /**
     * 设置数据
     * @param array $data
     * @return $this
     */
    public function data(array $data = []) {
        $this->options['data'] = $data;
        return $this;
    }

    /**
     * 数据排序
     * @param string $order
     * @return $this
     */
    public function order(string $order) {
        $this->options['order'] = $order;
        return $this;
    }

    /**
     * 数量
     * @param $limit
     * @return $this
     */
    public function limit($limit) {
        $this->options['limit'] = $limit;
        return $this;
    }

    /**
     * 设置条件
     * @param array $where
     * @return $this
     */
    public function where(array $where = []) {
        $this->options['where'] = $where;
        return $this;
    }

    /**
     * 查询多数据
     * @return array
     */
    public function select() {
        $data = $this->getObj()->select($this->_getTable(), $this->_getWhere(), $this->_getField(), $this->_getOrder(), $this->_getLimit());
        return empty($data) ? [] : $data;
    }

    /**
     * 查询数据
     * @return int
     */
    public function count() {
        $count = $this->getObj()->count($this->_getTable(), $this->_getWhere());
        return $count ? $count : 0;
    }

    /**
     * 查询单条数据
     * @return array
     */
    public function find() {
        $data = $this->limit(1)->select();
        return isset($data[0]) ? $data[0] : [];
    }

    /**
     * 插入数据
     * @param array $data
     * @return bool|mixed
     */
    public function insert($data = []) {
        $ids = $this->insertAll($data);
        if (!$ids) {
            return false;
        }
        return $ids[0];
    }

    /**
     * 批量插入数据
     * @param array $data
     * @return bool
     */
    public function insertAll($data = []) {

        if (!empty($data)) {
            $this->data($data);
        }

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
        $ids = $this->getObj()->insert($table, $datas);
        if ($ids === false) {
            return false;
        }
        return $ids;
    }

    /**
     * 更新数据
     * @return bool
     */
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
        return $this->getObj()->update($table, $where, $datas);
    }

    /**
     * 删除数据
     * @return bool
     */
    public function delete() {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->delete($this->_getTable(), $this->_getWhere());
        return ($status === false) ? false : $status;
    }

    /**
     * 递增数据
     * @param string $field
     * @param int $num
     * @return bool
     */
    public function setInc(string $field, int $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->setInc($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : $status;
    }

    /**
     * 递减数据
     * @param string $field
     * @param int $num
     * @return bool
     */
    public function setDec(string $field, int $num = 1) {
        if (empty($this->options['where']) || !is_array($this->options['where'])) {
            return false;
        }
        $status = $this->getObj()->setDec($this->_getTable(), $this->_getWhere(), $field, $num);
        return ($status === false) ? false : $status;
    }

    /**
     * 聚合查询
     * @param $group
     * @return mixed
     */
    public function aggregate($group) {
        return $this->getObj()->aggregate($this->_getTable(), $this->_getWhere(), $group);
    }

    /**
     * 去重查询
     * @param $group
     * @return mixed
     */
    public function distinct($group) {
        return $this->getObj()->distinct($this->_getTable(), $this->_getWhere(), $group);
    }

    /**
     * 数据求和
     * @param $field
     * @return int
     */
    public function sum(string $field) {
        $sum = $this->getObj()->sum($this->_getTable(), $this->_getWhere(), $field);
        return empty($sum) ? 0 : $sum;
    }

    /**
     * 获取字段
     * @return mixed
     */
    public function getFields() {
        return $this->getObj()->getFields();
    }

    /**
     * 获取主键
     * @return mixed
     */
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
            return [];
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

    /**
     * mox 模型对象
     * @return null
     */
    public function getObj() {
        $key = 'mox_' . http_build_query($this->config);
        if (!\dux\Dux::di()->has($key)) {
            $class = new $this->driver($this->config);
            if (!$class instanceof \dux\kernel\modelNo\ModelNoInterface) {
                throw new \Exception('The database class must interface class inheritance', 500);
            }
            $funArr = [
                'setPrimary' => $this->primary,
                'setFields' => array_keys($this->params),
                'setParams' => $this->params
            ];
            foreach ($funArr as $fun => $val) {
                if (!method_exists($class, $fun)) {
                    continue;
                }
                call_user_func([$class, $fun], $val);
            }
            \dux\Dux::di()->set($key, $class);
        }
        return \dux\Dux::di()->get($key);
    }

}