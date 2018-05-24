<?php

namespace dux\lib;

/**
 * 字符串处理类
 * @author Mr.L <349865361@qq.com>
 */
class Str {

    /**
     * 字符串截取
     * @param $str
     * @param $length
     * @param bool $suffix
     * @param string $charset
     * @return string
     */
    public static function strLen($str, $length, $suffix = true, $charset = "utf-8") {
        if ($charset != 'utf-8') {
            $str = mb_convert_encoding($str, 'utf8', $charset);
        }
        $osLen = mb_strlen($str);
        if ($osLen <= $length) {
            return $str;
        }
        $string = mb_substr($str, 0, $length, 'utf8');
        $sLen = mb_strlen($string, 'utf8');
        $bLen = strlen($string);
        $sCharCount = (3 * $sLen - $bLen) / 2;
        if ($osLen <= $sCharCount + $length) {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $length + 1, $osLen, 'utf8')); //将中英混合字符串分割成数组（UTF8下有效）
        } else {
            $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $length + 1, $sCharCount, 'utf8'));
        }
        foreach ($arr as $value) {
            if (ord($value) < 128 && ord($value) > 0) {
                $sCharCount = $sCharCount - 1;
            } else {
                $sCharCount = $sCharCount - 2;
            }
            if ($sCharCount <= 0) {
                break;
            }
            $string .= $value;
        }
        if ($suffix) return $string . '…';

        return $string;
    }


    /**
     * 字符串转码
     * @param  string $str 字符串
     * @param  string $from 原始编码
     * @param  string $to 目标编码
     * @return string
     */
    public static function strCharset($str, $from = 'gbk', $to = 'utf-8') {
        $from = strtoupper($from) == 'UTF8' ? 'utf-8' : $from;
        $to = strtoupper($to) == 'UTF8' ? 'utf-8' : $to;
        if (strtoupper($from) === strtoupper($to) || empty($str) || (is_scalar($str) && !is_string($str))) {
            return $str;
        }
        if (is_string($str)) {
            if (function_exists('mb_convert_encoding')) {
                return mb_convert_encoding($str, $to, $from);
            } elseif (function_exists('iconv')) {
                return iconv($from, $to, $str);
            } else {
                return $str;
            }
        } elseif (is_array($str)) {
            foreach ($str as $key => $val) {
                $_key = self::strCharset($key, $from, $to);
                $str[$_key] = self::strCharset($val, $from, $to);
                if ($key != $_key)
                    unset($str[$key]);
            }

            return $str;
        } else {
            return $str;
        }
    }

    /**
     * 截取摘要
     * @param $data
     * @param int $cut
     * @param string $str
     * @return mixed|string
     */
    public static function strMake($data, $cut = 0, $str = "...") {
        $data = self::htmlOut($data);
        $data = strip_tags($data);
        $pattern = "/&[a-zA-Z]+;/";
        $data = preg_replace($pattern, '', $data);
        $data = preg_replace('/\s(?=\s)/', '', $data);
        $data = preg_replace('/[\n\r\t]/', ' ', $data);
        $data = trim($data, ' ');
        if (!is_numeric($cut)) {
            return $data;
        }
        if ($cut > 0) {
            $data = mb_strimwidth($data, 0, $cut, $str);
        }

        return $data;
    }

    /**
     * 判断UTF-8
     * @param  string $string 字符串
     * @return boolean
     */
    public static function isUtf8($string) {
        if (!empty($string)) {
            $ret = json_encode(['code' => $string]);
            if ($ret == '{"code":null}') {
                return false;
            }
        }

        return true;
    }

    /**
     * 转义html
     * @param $str
     * @return string
     */
    public static function htmlIn($str) {
        if (function_exists('htmlspecialchars')) {
            $str = htmlspecialchars($str);
        } else {
            $str = htmlentities($str);
        }
        $str = addslashes($str);

        return $str;
    }

    /**
     * html代码还原
     * @param $str
     * @return string
     */
    public static function htmlOut($str) {
        if (function_exists('htmlspecialchars_decode')) {
            $str = htmlspecialchars_decode($str);
        } else {
            $str = html_entity_decode($str);
        }
        $str = stripslashes($str);

        return $str;
    }

    /**
     * html代码清理
     * @param $str
     * @return string
     */
    public static function htmlClear($str) {
        $str = self::htmlOut($str);
        $xss = new \dux\vendor\HtmlCleaner();

        return $xss->remove($str);
    }

    /**
     * 符号过滤
     * @param $text
     * @return string
     */
    public static function symbolClear($text) {
        if (trim($text) == '') return '';
        $text = preg_replace("/[[:punct:]\s]/", ' ', $text);
        $text = urlencode($text);
        $text = preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99|%EF%BD%9E|%EF%BC%8E|%EF%BC%88)+/", ' ', $text);
        $text = urldecode($text);

        return trim($text);
    }

    /**
     * 随机字符串
     * @param int $length
     * @return string
     */
    public static function randStr($length = 5) {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern{mt_rand(0, 35)};
        }

        return $key;
    }

    public static function numberUid($base = '') {
        mt_srand((double)microtime() * 1000000);

        return $base . date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function intFormat($str) {
        $str = preg_replace('/[^\-\d]*(\-?\d*).*/', '$1', $str);
        return $str ? $str : 0;
    }

    public static function priceFormat($str) {
        if (empty($str)) {
            return $str = 0;
        }

        return @number_format($str, 2, ".", "");
    }


    public static function priceCalculate($n1, $symbol, $n2, $scale = '2') {
        switch ($symbol) {
            case "+"://加法
                $res = bcadd($n1, $n2, $scale);
                break;
            case "-"://减法
                $res = bcsub($n1, $n2, $scale);
                break;
            case "*"://乘法
                $res = bcmul($n1, $n2, $scale);
                break;
            case "/"://除法
                $res = bcdiv($n1, $n2, $scale);
                break;
            case "%"://求余、取模
                $res = bcmod($n1, $n2, $scale);
                break;
            default:
                $res = 0;
                break;
        }

        return $res;
    }


}