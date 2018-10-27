<?php

namespace dux\lib;

/**
 * 请求验证过滤类
 *
 * @author  Mr.L <349865361@qq.com>
 */

class Filter {

    protected $data = [];
    protected $errorMsg = '';

    /**
     * 实例化过滤类
     * @param $data
     * @param array $fields
     */
    public function __construct($data = [], $fields = []) {
        $this->data = !empty($fields) ? array_intersect_key($data, array_flip($fields)) : $data;
    }

    /**
     * 验证数据
     * @param $rules
     * @return bool
     */
    public function validate($rules) {
        foreach ($rules as $field => $v) {
            list($ruleArray, $name) = $v;
            if (!in_array('required', $ruleArray)) {
                if (empty($this->data[$field])) {
                    continue;
                }
            }
            foreach ($ruleArray as $method => $params) {
                if (is_numeric($method)) {
                    $method = $params;
                }
                $method = 'validate' . ucfirst($method);
                if (!method_exists($this, $method)) {
                    $this->errorMsg = '验证规则不存在!';
                    return false;
                }
                if (!call_user_func_array([$this, $method], [$field, $this->data[$field], $params])) {
                    $this->errorMsg = str_replace('{name}', $name, $this->errorMsg);
                    return false;
                }
            }
        }
    }

    /**
     * 过滤数据
     * @param $rules
     * @return bool
     */
    public function filter($rules) {
        foreach ($rules as $field => $v) {
            list($ruleArray, $default) = $v;
            foreach ($ruleArray as $method => $params) {
                $method = 'filter' . ucfirst($method);
                if (!method_exists($this, $method)) {
                    $this->data[$field] = $default;
                } else {
                    $this->data[$field] = call_user_func_array([$this, $method], [$field, $this->data[$field], $params]);
                }
            }
            if (empty($this->data[$field])) {
                $this->data[$field] = $default;
            }
        }
        return $this->data;
    }

    /**
     * 判断必须
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateRequired($field, $value, $params) {
        if (empty($value)) {
            $this->errorMsg = '{name}不能为空!';
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        }
        return true;
    }

    /**
     * 判断字符长度
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateLen($field, $value, $params) {
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
            $this->errorMsg = '{name}不能为空!';
            return false;
        }

        if ($len > $max) {
            $this->errorMsg = '{name}位数不能超过' . $max . '!';
            return false;
        }
        if ($len < $min) {
            $this->errorMsg = '{name}位数不能小于' . $min . '!';
            return false;
        }
        return true;
    }

    /**
     * 判断是否URL
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateUrl($field, $value, $params) {
        if (!filter_var($value, \FILTER_VALIDATE_URL)) {
            $this->errorMsg = '{name}输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断邮箱
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateEmail($field, $value, $params) {
        if (!filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            $this->errorMsg = '{name}输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断数字
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateNumeric($field, $value, $params) {
        if (!is_numeric($value)) {
            $this->errorMsg = '{name}必须为数字!';
            return false;
        }
        return true;
    }

    /**
     * 判断整数
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateInt($field, $value, $params) {
        if (filter_var($value, \FILTER_VALIDATE_INT) === false) {
            $this->errorMsg = '{name}必须为整数!';
            return false;
        }
        return true;
    }

    /**
     * 判断IP
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateIp($field, $value, $params) {
        if (!filter_var($value, \FILTER_VALIDATE_IP)) {
            $this->errorMsg = '{name}输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断时间
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateDate($field, $value, $params) {
        if (strtotime($value) === false) {
            $this->errorMsg = '{name}格式输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断字符串
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateString($field, $value, $params) {
        if (!is_string($value)) {
            $this->errorMsg = '{name}必须为字符串!';
            return false;
        }
        return true;
    }

    /**
     * 判断中文
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validateChinese($field, $value, $params) {
        if (!preg_match("/^[\x{4e00}-\x{9fa5}]+$/u", $value)) {
            $this->errorMsg = '{name}必须为中文!';
            return false;
        }
        return true;
    }

    /**
     * 判断手机
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validatePhone($field, $value, $params) {
        if (!preg_match("/(^1[3|4|5|7|8][0-9]{9}$)/", $value)) {
            $this->errorMsg = '{name}输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断固定电话
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validateTel($field, $value, $params) {
        if (!preg_match("/^(^0\d{2}-?\d{8}$)|(^0\d{3}-?\d{7}$)|(^\(0\d{2}\)-?\d{8}$)|(^\(0\d{3}\)-?\d{7}$)$/", $value)) {
            $this->errorMsg = '{name}输入不正确!';
            return false;
        }
        return true;
    }

    /**
     * 判断银行卡
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validateCard($field, $value, $params) {
        $no = $value;
        $arr_no = str_split($no);
        $last_n = $arr_no[count($arr_no)-1];
        krsort($arr_no);
        $i = 1;
        $total = 0;
        foreach ($arr_no as $n){
            if($i%2==0){
                $ix = $n*2;
                if($ix>=10){
                    $nx = 1 + ($ix % 10);
                    $total += $nx;
                }else{
                    $total += $ix;
                }
            }else{
                $total += $n;
            }
            $i++;
        }
        $total -= $last_n;
        $total *= 9;
        return $last_n == ($total%10);

    }

    /**
     * 判断邮编
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validateZip($field, $value, $params) {
        if (!preg_match("/^\d{6}$/", $value)) {
            $this->errorMsg = '{name}输入不正确!';
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
    public function validateEmpty($field, $value, $params) {
        if(empty($value)) {
            return false;
        }
        return true;
    }


    /**
     * 判断正则
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function validateRegex($field, $value, $params) {
        if (!preg_match($params, $value)) {
            $this->errorMsg = '{name}';
            return false;
        }
        return true;
    }

    /**
     * 自定义判断对象
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateObject($field, $value, $params) {
        if (!call_user_func_array([$params[0], $params[1]], [$field, $value])) {
            $this->errorMsg = '{name}';
            return false;
        }
        return true;
    }

    /**
     * 判断照片
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function validateImage($field, $value, $params) {
        $value = str_replace('\\', '/', $value);
        $url = explode('/', $value, 2);
        $ext = explode('.', $value, 2);
        $ext = end($ext);
        $ext = strtolower($ext);
        if(!in_array($ext, ['jpg', 'gif', 'png', 'bmp'])) {
            return false;
        }
        if($params == 'local') {
            $url = end($url);
            if(!is_file(ROOT_PATH . $url)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 自定义判断函数
     */
    public function validateFunction($field, $value, $params) {
        if (!call_user_func($params, $value)) {
            $this->errorMsg = '{name}';
            return false;
        }
        return true;
    }

    /**
     * 获取错误消息
     * @return string
     */
    public function getError() {
        return $this->errorMsg;
    }

    /**
     * 字符串截取
     * @param $field
     * @param $value
     * @param $params
     */
    public function filterLen($field, $value, $params) {
        $rule = explode(',', $params, 2);
        if (count($rule) > 1) {
            $min = $rule[0];
            $max = $rule[1];
        } else {
            $min = 1;
            $max = $rule[0];
        }
        if (function_exists('mb_substr')) {
            mb_substr($value, $min, $max, 'utf-8');
        } else {
            substr($value, $min, $max);
        }
    }

    /**
     * 过滤URL
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function filterUrl($field, $value, $params) {
        return filter_var($value, \FILTER_VALIDATE_URL);
    }

    /**
     * 过滤EMAIL
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function filterEmail($field, $value, $params) {
        return filter_var($value, \FILTER_VALIDATE_EMAIL);
    }

    /**
     * 过滤数字
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function filterNumeric($field, $value, $params) {
        if(empty($params)){
            return (int) $value;
        }else{
            return number_format($value, $params);
        }
    }

    /**
     * 过滤整数
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function filterInt($field, $value, $params) {
        $rule = explode(',', $params, 2);
        if (count($rule) > 1) {
            $min = $rule[0];
            $max = $rule[1];
        } else {
            $min = 0;
            $max = $rule[0];
        }
        return filter_var($value, \FILTER_VALIDATE_INT, array("options"=> array("min_range"=>$min, "max_range"=>$max)));
    }

    /**
     * 过滤IP地址
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function filterIp($field, $value, $params) {
        return filter_var($value, \FILTER_VALIDATE_IP);
    }

    /**
     * 时间转时间戳
     * @param $field
     * @param $value
     * @param $params
     * @return mixed
     */
    public function filterTime($field, $value, $params) {
        return strtotime($value);
    }

    /**
     * 过滤非中文
     * @param $field
     * @param $value
     * @param $params
     * @return string
     */
    public function filterChinese($field, $value, $params) {
        preg_match_all("/[\x{4e00}-\x{9fa5}]+/u", $value, $chinese);
        return implode("", $chinese[0]);
    }

    /**
     * 过滤html
     */
    public function filterHtml($field, $value, $params = array()) {
        $xss = new \dux\vendor\HtmlCleaner($params[0], $params[1]);
        return $xss->remove($value);
    }

    /**
     * 过滤字符串
     */
    public function filterString($field, $text, $params) {
        $text = preg_replace("'<script[^>]*>.*?</script>'si", '', $text);
        $text = preg_replace('/<a\s+.*?href="([^"]+)"[^>]*>([^<]+)<\/a>/is', '\2 (\1)', $text);
        $text = preg_replace('/<!--.+?-->/', '', $text);
        $text = preg_replace('/{.+?}/', '', $text);
        $text = preg_replace('/&nbsp;/', ' ', $text);
        $text = preg_replace('/&amp;/', ' ', $text);
        $text = preg_replace('/&quot;/', ' ', $text);
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
        return trim($text);
    }

    /**
     * 过滤正则
     * @param $field
     * @param $value
     * @param $params
     * @return int
     */
    public function filterRegex($field, $value, $params) {
        return preg_replace($params, '', $value);
    }

    /**
     * 自定义过滤方法
     * @param $field
     * @param $value
     * @param $params
     * @return bool
     */
    public function filterObject($field, $value, $params) {
        return call_user_func_array([$params[0], $params[1]], [$field, $value]);
    }

    /**
     * 自定义过滤函数
     */
    public function filterFunction($field, $value, $params) {
        return call_user_func($params, $value);
    }


}