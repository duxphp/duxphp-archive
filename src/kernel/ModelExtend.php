<?php

/**
 * 模型扩展
 */

namespace dux\kernel;

class ModelExtend extends Model {

    protected $error = '';
    protected $data = [];
    protected $infoModel = [];
    protected $primary = '';

    protected $validateRule = [];
    protected $formatRule = [];

    public function __construct($config=[]) {
        if( empty($config) ){
            $config = \dux\Config::get('dux.database');
        }
        $this->driver = $config['type'];
        $this->prefix = $config['prefix'];
        $this->config = $config;
        parent::__construct();
        if (empty($this->primary)) {
            $this->primary = $this->infoModel['pri'];
        }
        if (empty($this->formatRule)) {
            $this->formatRule = (array) $this->infoModel['format'];
        }
        if (empty($this->validateRule)) {
            $this->validateRule = (array) $this->infoModel['validate'];
        }
    }

    /**
     * 创建处理数据
     * @param array $data
     * @param string|null $time
     * @return array|bool|false|mixed|string
     */
    public function create(array $data = [], string $time = null) {
        if (empty($data)) {
            $data = request('post');
        }
        if (empty($data)) {
            dux_log('Create the data does not exist!');
        }
        if (empty($time)) {
            $time = $data[$this->primary] ? 'edit' : 'add';
        }
        $this->data = $data;
        if (!$this->formatData($this->formatRule, $time)) {
            return false;
        }
        if (!$this->validateData($this->validateRule, $time)) {
            return false;
        }
        return $this->data;
    }

    /**
     * 验证规则
     * @param array $rules
     * @return $this
     */
    public function validate(array $rules = []) {
        $this->validateRule = $rules;
        return $this;
    }

    /**
     * 格式化规则
     * @param array $rules
     * @return $this
     */
    public function format(array $rules = []) {
        $this->formatRule = $rules;
        return $this;
    }

    /**
     * 格式化数据
     * @param array $formatRule
     * @param string $time
     * @return bool
     */
    public function formatData(array $formatRule = [], string $time = 'all') {
        //获取自动处理
        if (empty($formatRule)) {
            return true;
        }
        $data = $this->data;
        $filter = (new \dux\lib\Filter())->filter();
        foreach ($formatRule as $field => $val) {
            foreach ($val as $method => $v) {
                $method = lcfirst($method);
                [$params, $trigger, $type] = $v;
                $type = isset($type) ? $type : 1;
                if (!$type) {
                    if (!isset($data[$field])) {
                        continue;
                    }
                }
                if ($trigger <> $time && $trigger <> 'all') {
                    continue;
                }
                switch ($method) {
                    case 'callback':
                        $data[$field] = call_user_func_array([&$this, $params], [$field, $data[$field]]);
                        break;
                    case 'field':
                        $data[$field] = $data[$params];
                        break;
                    case 'ignore':
                        if (empty($data[$field])) {
                            unset($data[$field]);
                        }
                        break;
                    case 'string':
                        if (empty($data[$field])) {
                            $data[$field] = $params;
                        }
                        break;
                    default:
                        if (!method_exists(\dux\lib\FilterInner::class, $method)) {
                            return $this->error("{$method} filtering method does not exist!");
                        } else {
                            $data[$field] = $filter->$method($data[$field], $params);
                        }
                }
            }
        }
        if (empty($data)) {
            return $this->error('Submit data is not correct!');
        }
        $this->data = $data;
        return true;
    }

    /**
     * 验证数据
     * @param array $validateRule
     * @param string $time
     * @return bool
     * @throws \Exception
     */
    public function validateData(array $validateRule = [], string $time = 'all') {
        if (empty($validateRule)) {
            return true;
        }
        $data = $this->data;
        $filter = (new \dux\lib\Filter())->verify();
        foreach ($validateRule as $field => $val) {
            foreach ($val as $method => $v) {
                $method = lcfirst($method);
                [$params, $msg, $where, $trigger] = $v;
                if (empty($trigger)) {
                    $trigger = 'all';
                }
                $value = $data[$field];
                if ($where == 'exists') {
                    if (!isset($data[$field])) {
                        continue;
                    }
                }
                if ($where == 'value') {
                    if (empty($value)) {
                        continue;
                    }
                }
                if ($trigger <> $time && $trigger <> 'all') {
                    continue;
                }
                switch ($method) {
                    case 'callback':
                        if (!call_user_func_array([&$this, $params], [$field, $value, $time])) {
                            return $this->error($msg);
                        }
                        break;
                    case 'confirm':
                        if ($value == $data[$params]) {
                            return $this->error($msg);
                        }
                        break;
                    case 'unique':
                        $where = [];
                        $where[$field] = $value;
                        if ($time == 'edit') {
                            $where[$this->primary . '[!]'] = $data[$this->primary];
                        }
                        if ($this->where($where)->count()) {
                            return $this->error($msg);
                        }
                        break;
                    default:
                        if (!method_exists(\dux\lib\VerifyInner::class, $method)) {
                            return $this->error("{$method} filtering method does not exist!");
                        }
                        if (!$filter->$method($value, $params)) {
                            return $this->error($msg);
                        }
                }
            }
        }
        return true;
    }

    /**
     * 获取处理数据
     * @return array
     */
    public function getData() {
        return $this->data;
    }

    /**
     * 获取主键
     * @return string
     */
    public function getPrimary() {
        return $this->primary;
    }

    /**
     * 成功
     * @param $data
     * @return bool
     */
    public function success($data) {
        return $data ? $data : true;
    }

    /**
     * 失败
    * @param $msg
     * @return bool
     * @throws \dux\exception\Message
     */
    public function error($msg) {
        dux_error($msg);
        return false;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError() {
        return $this->error;
    }

    /**
     * 添加数据
     * @param array $data
     * @return bool|mixed
     * @throws \Exception
     */
    public function add(array $data = []) {
        unset($data[$this->primary]);
        return $this->data($data)->insert();
    }

    /**
     * 编辑数据
     * @param array $data
     * @return bool|mixed
     * @throws \Exception
     */
    public function edit(array $data = []) {
        $where = [];
        $where[$this->primary] = $data[$this->primary];
        return $this->data($data)->where($where)->update();
    }

    /**
     * 删除数据
     * @param int $id
     * @return bool|mixed
     * @throws \Exception
     */
    public function del(int $id) {
        $where = [];
        $where[$this->primary] = $id;
        return $this->where($where)->delete();
    }


}