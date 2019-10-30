<?php

/**
 * 判断AJAX
 */
function isAjax() {
    if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && $_SERVER['HTTP_X_DUX_AJAX']) {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断GET
 */
function isGet() {
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断POST
 */
function isPost() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断微信访问
 * @return bool
 */
function isWechat() {
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && !$_GET['webapp']) {
        return true;
    }

    return false;
}

/**
 * 判断APP访问
 * @return bool
 */
function isApp() {
    if ($_SERVER['HTTP_FROM'] == 'app' || $_GET['webapp']) {
        return true;
    } else {
        return false;
    }
}

/**
 * 判断API请求
 * @return bool
 */
function isApi() {
    return isset($_SERVER['HTTP_TOKEN']) ? true : false;
}

/**
 * 依赖注入
 * @return \dux\com\Di|null
 */
function di() {
    return \dux\Dux::di();
}

/**
 * 获取钩子类
 * @param $layer
 * @param $name
 * @param $method
 * @param array $vars
 * @return array|null
 */
function hook($layer, $name, $method, $vars = []) {
    if (empty($name)) {
        return null;
    }

    $apiPath = APP_PATH . '*/' . $layer . '/' . ucfirst($name) . ucfirst($layer) . '.php';

    $apiList = glob($apiPath);

    if (empty($apiList)) {
        return [];
    }
    $appPathStr = strlen(APP_PATH);
    $method = 'get' . ucfirst($method) . ucfirst($name);

    $data = [];
    foreach ($apiList as $value) {
        $path = substr($value, $appPathStr, -4);
        $path = str_replace('\\', '/', $path);
        $appName = explode('/', $path);
        $appName = $appName[0];
        $config = load_config('app/' . $appName . '/config/config', false);
        if (!$config['app.system'] && (!$config['app.state'] || !$config['app.install'])) {
            continue;
        }
        $class = '\\app\\' . $appName . '\\' . $layer . '\\' . ucfirst($name) . ucfirst($layer);
        if (!class_exists($class)) {
            return null;
        }
        $class = target($appName . '/' . $name, $layer);
        if (method_exists($class, $method)) {
            $data[$appName] = call_user_func_array([$class, $method], $vars);
        }
    }

    return $data;
}

/**
 * @param $layer
 * @param $name
 * @param $method
 * @param array $vars
 * @param bool $error
 * @return array|null
 */
function run($layer, $name, $method, $vars = [], $error = false) {
    if (empty($name)) {
        return null;
    }

    $apiPath = APP_PATH . '*/' . $layer . '/' . ucfirst($name) . ucfirst($layer) . '.php';

    $apiList = glob($apiPath);

    if (empty($apiList)) {
        return [];
    }
    $appPathStr = strlen(APP_PATH);

    $data = [];
    foreach ($apiList as $value) {
        $path = substr($value, $appPathStr, -4);
        $path = str_replace('\\', '/', $path);
        $appName = explode('/', $path);
        $appName = $appName[0];
        $config = load_config('app/' . $appName . '/config/config', false);
        if (!$config['app.system'] && (!$config['app.state'] || !$config['app.install'])) {
            continue;
        }
        $class = '\\app\\' . $appName . '\\' . $layer . '\\' . ucfirst($name) . ucwords($layer);

        if (!class_exists($class)) {
            return null;
        }
        $class = target($appName . '/' . $name, $layer);
        if (method_exists($class, $method)) {
            $data[$appName] = call_user_func_array([$class, $method], $vars);
        }
    }

    return $data;
}

/**
 * 卸载请求数据
 * @param array $data
 */
function unRequest($data = []) {
    foreach ($data as $vo) {
        unset($_POST[$vo]);
        unset($_GET[$vo]);
    }
}

/**
 * 允许请求数据
 * @param array $data
 */
function inRequest($data = []) {
    foreach ($data as $vo) {
        if (!in_array($vo, $_POST) && !in_array($vo, $_GET)) {
            unset($_POST[$vo]);
            unset($_GET[$vo]);
        }
    }
}

/**
 * 获取请求参数
 * @param string $method
 * @param string $key
 * @param string $default
 * @param string $function
 * @return array|mixed|string
 */
function request($method = '', $key = '', $default = '', $function = '') {
    return \dux\Dux::request($method, $key, $default, $function);
}

/**
 * string简化URL方法
 * @param string $str
 * @param array $params
 * @param bool $domain
 * @param bool $ssl
 * @param bool $global
 * @return string
 */
function url($str = '', $params = [], $domain = false, $ssl = true, $global = true) {
    return \dux\Dux::url($str, $params, $domain, $ssl, $global);
}

/**
 * 简化类调用
 * @param $class
 * @param string $layer
 * @return mixed
 */
function target($class, $layer = 'model') {
    return \dux\Dux::target($class, $layer);
}

/**
 * 简化类配置加载
 */
function load_config($file, $enforce = true) {
    return \dux\Dux::loadConfig($file, $enforce);
}

/**
 * 配置保存
 * @param $file
 * @param $config
 * @return array|bool
 */
function save_config($file, $config) {
    return \dux\Dux::saveConfig($file, $config);
}

/**
 * 二维数组排序
 * @param $data
 * @param $key
 * @param string $type
 * @return mixed
 */
function array_sort($data, $key, $type = 'asc') {
    if (empty($data)) {
        return $data;
    }
    $keys = [];
    foreach ($data as $k => $v) {
        $keys[] = $v[$key];
    }
    if ($type == 'asc') {
        $sort = SORT_ASC;
    } else {
        $sort = SORT_DESC;
    }
    array_multisort($keys, $sort, $data);

    return $data;
}

/**
 * 数据签名
 * @param $data
 * @return mixed
 */
function data_sign($data) {
    $config = \dux\Config::get('dux.use');
    if (!is_array($data)) {
        $data = [
            'data' => $data,
        ];
    }
    ksort($data);
    return url_base64_encode(hash_hmac('sha1', http_build_query($data), $config['safe_key'], true));
}

/**
 * 验证签名
 * @param $data
 * @param string $sign
 * @return bool
 */
function data_sign_has($data, $sign = '') {
    if (empty($sign)) {
        return false;
    }
    if (!is_array($data)) {
        $data = [
            'data' => $data,
        ];
    }
    $sign = url_base64_decode($sign);
    ksort($data);
    $config = \dux\Config::get('dux.use');
    $valToken = hash_hmac('sha1', http_build_query($data), $config['safe_key'], true);
    return ($sign == $valToken);
}

/**
 * base64 URL编码
 * @param $string
 * @return mixed|string
 */
function url_base64_encode($string) {
    $data = base64_encode($string);
    $data = str_replace(['+', '/', '='], ['-', '_', ''], $data);
    return $data;
}

/**
 * base64 URL解码
 * @param $string
 * @return bool|string
 */
function url_base64_decode($string) {
    $data = str_replace(['-', '_'], ['+', '/'], $string);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

/**
 * 遍历所有文件和目录
 * @param $dir
 * @return array
 */
function list_dir($dir) {
    $dir .= substr($dir, -1) == '/' ? '' : '/';
    $dirInfo = [];
    foreach (glob($dir . '*') as $v) {
        $dirInfo[] = $v;
        if (is_dir($v)) {
            $dirInfo = array_merge($dirInfo, list_dir($v));
        }
    }
    return $dirInfo;
}

/**
 * 复制目录
 * @param $sourceDir
 * @param $aimDir
 * @return bool
 */
function copy_dir($sourceDir, $aimDir) {
    $succeed = true;
    if (!file_exists($aimDir)) {
        if (!mkdir($aimDir, 0777)) {
            return false;
        }
    }
    $objDir = opendir($sourceDir);
    while (false !== ($fileName = readdir($objDir))) {
        if (($fileName != ".") && ($fileName != "..")) {
            if (!is_dir("$sourceDir/$fileName")) {
                if (!copy("$sourceDir/$fileName", "$aimDir/$fileName")) {
                    $succeed = false;
                    break;
                }
            } else {
                copy_dir("$sourceDir/$fileName", "$aimDir/$fileName");
            }
        }
    }
    closedir($objDir);
    return $succeed;
}

/**
 * 删除目录
 * @param $dir
 * @return bool
 */
function del_dir($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    $handle = opendir($dir);
    while (($file = readdir($handle)) !== false) {
        if ($file != "." && $file != "..") {
            is_dir("$dir/$file") ? del_dir("$dir/$file") : @unlink("$dir/$file");
        }
    }
    if (readdir($handle) == false) {
        closedir($handle);
        @rmdir($dir);
    }
}

/**
 * 隐藏字符串
 * @param $string 字符串
 * @param int $start 开始位置
 * @param int $length 长度
 * @param string $re 替换符
 * @return bool|string
 */
function hide_str($string, $start = 0, $length = 0, $re = '*') {
    if (empty($string)) return false;
    $strarr = array();
    $mb_strlen = mb_strlen($string);
    while ($mb_strlen) {//循环把字符串变为数组
        $strarr[] = mb_substr($string, 0, 1, 'utf8');
        $string = mb_substr($string, 1, $mb_strlen, 'utf8');
        $mb_strlen = mb_strlen($string);
    }
    $strlen = count($strarr);
    $begin  = $start >= 0 ? $start : ($strlen - abs($start));
    $end    = $last   = $strlen - 1;
    if ($length > 0) {
        $end  = $begin + $length - 1;
    } elseif ($length < 0) {
        $end -= abs($length);
    }
    for ($i=$begin; $i<=$end; $i++) {
        $strarr[$i] = $re;
    }
    if ($begin >= $end || $begin >= $last || $end > $last) return false;
    return implode('', $strarr);
}

/**
 * 日志写入
 * @param string $msg
 * @param string $type
 * @param string $fileName
 * @return bool
 * @throws Exception
 */
function dux_log($msg = '', $type = 'INFO', $fileName = '') {
    return \dux\Dux::log($msg, $type, $fileName);
}

/**
 * 人性化时间
 * @param $time
 * @return string
 */
function date_tran($time) {
    $agoTime = (int)$time;
    $time = time() - $agoTime;
    if ($time >= 31104000) {
        return date('Y年m月', $time);
    }
    if ($time >= 2592000) {
        return date('m月d日', $time);
    }
    if ($time >= 86400) {
        $num = (int)($time / 86400);
        return $num . '天前';
    }
    if ($time >= 3600) {
        $num = (int)($time / 3600);
        return $num . '小时前';
    }
    if ($time > 60) {
        $num = (int)($time / 60);
        return $num . '分钟前';
    }
    return '刚刚';
}

/**
 * HTML转义
 * @param $html
 * @return string
 */
function html_in($html = '') {
    return \dux\lib\Filter::filter()->htmlIn($html);

}

/**
 * HTML反转义
 * @param $str
 * @return string
 */
function html_out($str = '') {
    return \dux\lib\Filter::filter()->htmlOut($str);
}

/**
 * 清理HTML代码
 * @param $str
 * @return string
 */
function html_clear($str = '') {
    return \dux\lib\Filter::filter()->html($str);
}

/**
 * 文本转html
 * @param $str
 * @return mixed
 */
function str_html($str = '') {
    $str = str_replace("\n", '<br>', $str);
    return $str;
}

/**
 * 等宽度截取
 * @param $str
 * @param int $len
 * @param bool $suffix
 * @return string
 */
function str_len($str, $len = 20, $suffix = true) {
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
        $arr = preg_split('/(?<!^)(?!$)/u', mb_substr($str, $length + 1, $osLen, 'utf8'));
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
 * 格式化为数字
 * @param $str
 * @return int|mixed
 */
function int_format($str = 0) {
    return \dux\lib\Filter::filter()->number($str);
}

/**
 * 价格格式化不带千分位
 * @param $money
 * @return string
 */
function price_format($money = 0) {
    return \dux\lib\Filter::filter()->price($money);
}

/**
 * 精准计算
 * @param $n1
 * @param $symbol
 * @param $n2
 * @param string $scale
 * @return int|string
 */
function price_calculate($n1, $symbol, $n2, $scale = '2') {
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

/**
 * 生成单号
 * @param string $pre
 * @return string
 */
function log_no($pre = '') {
    mt_srand((double)microtime() * 1000000);
    return $pre . date('Ymd') . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * 基础UI库
 * @return string
 */
function load_ui($path = '', $cssLoad = true) {
    $css = ROOT_URL . '/public/common/css/dux.css?v=1.0.9';
    $js = ROOT_URL . '/public/common/js/dux.min.js?v=1.0.9';
    $data = [];
    if ($cssLoad) {
        $data[] = '<link rel="stylesheet" href="' . $css . '">' . "\r\n";
    }
    $data[] = '<script type="text/javascript" src="' . $js . '" data-cfg-autoload="false" data-debug="' . ($config['debug_browser'] ? true : false) . '" data-path="' . $path . '/" data-role="' . ROLE_NAME . '" data-root="' . ROOT_URL . '"></script>' . "\r\n";
    return join("", $data);
}

/**
 * 常用js库
 * @param string $name
 * @return mixed
 */
function load_js($name = 'jquery') {
    $data = [
        'jquery' => 'https://lib.baomitu.com/jquery/3.4.1/jquery.min.js',
        'vue' => 'https://lib.baomitu.com/vue/2.6.10/vue.min.js',
    ];
    $nameArray = explode(',', $name);
    $returnData = [];
    foreach ($nameArray as $vo) {
        $returnData[] = '<script type="text/javascript" src="' . $data[$vo] . '"></script>' . "\r\n";
    }
    return join("", $returnData);
}

/**
 * 对象转list
 * @param $objList
 * @param array $keyList
 * @return array
 */
function object_to_array($objList, $keyList = ['key', 'text']) {
    $list = [];
    if (!$objList) {
        return [];
    }
    foreach ($objList as $k => $v) {
        $list[] = [
            $keyList[0] => $k,
            $keyList[1] => $v,
        ];
    }
    return $list;
}