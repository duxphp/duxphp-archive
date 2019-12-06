<?php

namespace dux\lib;

/**
 * 验证过滤类
 *
 * @author  Mr.L <admin@duxphp.com>
 */
class Filter {

    protected $data = [];
    protected $errorMsg = '';

    private static $verifyObjcet = null;
    private static $filterObject = null;

    /**
     * 数据验证
     * @param array $data
     * @param array $rules
     * @throws \dux\exception\Error
     */
    public static function verifyArray(array $data = [], array $rules = []) {
        /*$rules = [
            'field' => [
                'rule' => ['params', 'desc'],
            ],
        ];*/
        foreach ($rules as $field => $rule) {
            foreach ($rule as $method => $ruleArray) {
                list($desc, $params) = $ruleArray;
                if (!method_exists(self::verify(), $method)) {
                    throw new \dux\exception\Error("Validation rules does not exist！");
                }
                if (!self::verify()->$method($data[$field], $params)) {
                    throw new \dux\exception\Error($desc);
                }
            }
        }
    }

    /**
     * 数据过滤
     * @param array $data
     * @param array $rules
     * @return array
     * @throws \dux\exception\Error
     */
    public static function filterArray(array $data = [], array $rules = []) {
        /*$rules = [
            'field' => [
                'rule' => 'params',
            ],
        ];*/
        $tmpData = [];
        foreach ($rules as $field => $rule) {
            foreach ($rule as $method => $params) {
                if (is_int($method)) {
                    $method = $params;
                    $params = null;
                }
                if (!method_exists(self::filter(), $method)) {
                    throw new \dux\exception\Error("Filter rules does not exist！");
                }
                $tmpData[$field] = self::filter()->$method($data[$field], $params);
            }
        }
        return $tmpData;
    }

    /**
     * 验证对象
     * @return VerifyInner|null
     */
    public static function verify() {
        if (!self::$verifyObjcet) {
            self::$verifyObjcet = new VerifyInner();
        }
        return self::$verifyObjcet;
    }

    /**
     * 过滤对象
     * @return FilterInner|null
     */
    public static function filter() {
        if (!self::$filterObject) {
            self::$filterObject = new FilterInner();
        }
        return self::$filterObject;
    }
}


class FilterInner {

    /**
     * 字符串截取
     * @param $value
     * @param $params
     */
    public function len($value, $params) {
        $rule = explode(',', $params, 2);
        if (count($rule) > 1) {
            list($min, $mix) = $rule;
        } else {
            $min = 1;
            $max = $rule[0];
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, $min, $max, 'utf-8');
        } else {
            return substr($value, $min, $max);
        }
    }

    /**
     * 过滤URL
     * @param $value
     * @return mixed|string
     */
    public function url($value) {
        $value = filter_var($value, \FILTER_VALIDATE_URL);
        if ($value === false) {
            return '';
        }
        return $value;
    }

    /**
     * 过滤EMAIL
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function email($value) {
        $value = filter_var($value, \FILTER_VALIDATE_EMAIL);
        if ($value === false) {
            return '';
        }
        return $value;
    }

    /**
     * 过滤数字
     * @param $value
     * @return mixed|string
     */
    public function number($value) {
        $value = filter_var($value, \FILTER_VALIDATE_FLOAT);
        if ($value === false) {
            return 0;
        }
        return $value;
    }

    /**
     * 过滤整数
     * @param $value
     * @param $params
     * @return int|mixed
     */
    public function int($value, $params) {
        $rule = explode(',', $params, 2);
        if (count($rule) > 1) {
            list($min, $max) = $rule;
        } else {
            $min = 0;
            $max = $rule[0];
        }
        $value = filter_var($value, \FILTER_VALIDATE_INT, ["options" => ["min_range" => $min, "max_range" => $max]]);
        if ($value === false) {
            return 0;
        }
        return $value;
    }

    /**
     * 过滤IP地址
     * @param $value
     * @return mixed|string
     */
    public function filterIp($value) {
        $value = filter_var($value, \FILTER_VALIDATE_IP);
        if ($value === false) {
            return '';
        }
        return $value;
    }

    /**
     * 时间转时间戳
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function time($value) {
        return strtotime($value);
    }

    /**
     * 过滤非中文
     * @param $field
     * @param $value
     * @param $params
     * @return string
     */
    public function chinese($value, $params) {
        preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $value, $chinese);
        return implode("", $chinese[0]);
    }

    /**
     * 过滤html
     */
    public function html($value) {
        $value = filter_var($this->htmlOut($value), \FILTER_SANITIZE_STRING);
        if ($value === false) {
            return '';
        }
        return $value;
    }

    /**
     * html转义
     * @param $value
     * @return string
     */
    public function htmlIn($value) {
        $value = htmlspecialchars($value);
        return addslashes($value);
    }

    /**
     * html还原
     * @param $value
     * @return string
     */
    public function htmlOut($value) {
        $value = htmlspecialchars_decode($value);
        return stripslashes($value);
    }

    /**
     * 过滤价格
     * @param $value
     * @return string
     */
    public function price($value) {
        return number_format($this->number($value), 2, ".", "");
    }

    /**
     *  过滤正则
     * @param $value
     * @param $params
     * @return string|string[]|null
     */
    public function regex($value, $params) {
        return preg_replace($params, '', $value);
    }

    /**
     * 对象过滤
     * @param $value
     * @param $params
     * @return mixed
     */
    public function object($value, $params) {
        return call_user_func_array([$params[0], $params[1]], [$value]);
    }

    /**
     * 函数过滤
     * @param $value
     * @param $params
     * @return mixed
     */
    public function function($value, $params) {
        if (!empty($value)) {
            return call_user_func($params, $value);
        } else {
            return call_user_func($params);
        }
    }
}

class VerifyInner {

    /**
     * 判断必须
     * @param $value
     * @return bool
     */
    public static function required($value) {
        if (empty($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }
        return true;
    }

    /**
     * 字符长度
     * @param $value
     * @param $params
     * @return bool
     */
    public static function len($value, $params) {
        $rule = explode(',', $params, 2);
        if (count($rule) > 1) {
            $min = $rule[0];
            $max = $rule[1];
        } else {
            $min = 1;
            $max = $rule[0];
        }
        if (function_exists('mb_strlen')) {
            $len = mb_strlen($value, 'utf8');
        } else {
            $len = strlen($value);
        }
        if (!$len) {
            return false;
        }
        if ($len > $max) {
            return false;
        }
        if ($len < $min) {
            return false;
        }
        return true;
    }

    /**
     * 验证Url
     * @param $value
     * @return bool
     */
    public static function url($value) {
        if (filter_var($value, \FILTER_VALIDATE_URL, \FILTER_FLAG_SCHEME_REQUIRED) === false) {
            return false;
        }
        return true;
    }

    /**
     * 验证邮箱
     * @param $value
     * @return bool
     */
    public function email($value) {
        if (filter_var($value, \FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }
        return true;
    }

    /**
     * 验证数字
     * @param $value
     * @return bool
     */
    public function number($value) {
        if (filter_var($value, \FILTER_VALIDATE_FLOAT) === false) {
            return false;
        }
        return true;
    }

    /**
     * 验证整数
     * @param $value
     * @return bool
     */
    public function int($value) {
        if (filter_var($value, \FILTER_VALIDATE_INT) === false) {
            return false;
        }
        return true;
    }

    /**
     * 验证IP
     * @param $value
     * @return bool
     */
    public function ip($value) {
        if (!filter_var($value, \FILTER_VALIDATE_IP)) {
            return false;
        }
        return true;
    }

    /**
     * 验证日期
     * @param $value
     * @param null $params
     * @return bool
     */
    public function date($value, $params = null) {
        if (date($params ?: 'Y-m-d', strtotime($value)) == $value) {
            return false;
        }
        return true;
    }

    /**
     * 判断字符串
     * @param $value
     * @return bool
     */
    public function string($value) {
        if (!is_string($value)) {
            return false;
        }
        return true;
    }

    /**
     * 判断中文
     * @param $value
     * @return bool
     */
    public function chinese($value) {
        if (filter_var($value, \FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => "/^[\x{4e00}-\x{9fa5}]+$/u"]]) === false) {
            return false;
        }
        return true;
    }

    /**
     * 判断手机
     * @param $value
     * @return bool
     */
    public function phone($value) {
        if (filter_var($value, \FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => "/^1\d{10}$/"]]) === false) {
            return false;
        }
        return true;
    }

    /**
     * 判断固定电话
     * @param $value
     * @return bool
     */
    public function tel($value) {
        if (filter_var($value, \FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => "/^(^0\d{2}-?\d{8}$)|(^0\d{3}-?\d{7}$)|(^\(0\d{2}\)-?\d{8}$)|(^\(0\d{3}\)-?\d{7}$)$/"]]) === false) {
            return false;
        }
        return true;
    }

    /**
     * 验证银行卡
     * @param $value
     * @return bool
     */
    public function card($value) {
        $no = $value;
        $arr_no = str_split($no);
        $last_n = $arr_no[count($arr_no) - 1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n) {
            if ($i % 2 == 0) {
                $ix = $n * 2;
                if ($ix >= 10) {
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                } else {
                    $total += $ix;
                }
            } else {
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        return $last_n == ($total % 10);

    }

    /**
     * 验证邮编
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function zip($value) {
        if (filter_var($value, \FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => "/^\d{6}$/"]]) === false) {
            return false;
        }
        return true;
    }

    /**
     * 判断是否空
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function empty($value) {
        if (empty($value)) {
            return false;
        }
        return true;
    }

    /**
     * 判断正则
     * @param $value
     * @param $params
     * @return int
     */
    public function regex($value, $params) {
        if (filter_var($value, \FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => $params]]) === false) {
            return false;
        }
        return true;
    }

    /**
     * 自定义判断对象
     * @param $value
     * @param $params
     * @return bool
     */
    public function object($value, $params) {
        if (!call_user_func_array([$params[0], $params[1]], [$value])) {
            return false;
        }
        return true;
    }

    /**
     * 自定义判断函数
     * @param $value
     * @param $params
     * @return bool
     */
    public function function($value, $params) {
        if (!call_user_func($params, $value)) {
            return false;
        }
        return true;
    }
}