<?php


/**
 * 判断AJAX
 */
function isAjax() {
    if ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') && !isset($_GET['ajax'])) {
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
 * 获取钩子类
 * @param $layer
 * @param $name
 * @param $method
 * @param array $vars
 * @return array|null
 */
function hook($layer, $name, $method, $vars = []) {
    if (empty($name)) return null;
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
    if (empty($name)) return null;
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
            'data' => $data
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
            'data' => $data
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
    $data = str_replace(array('+','/','='),array('-','_',''),$data);
    return $data;
}

/**
 * base64 URL解码
 * @param $string
 * @return bool|string
 */
function url_base64_decode($string) {
    $data = str_replace(array('-','_'),array('+','/'),$string);
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
 * @param $string
 * @param int $bengin
 * @param int $len
 * @param int $type
 * @param string $glue
 * @return bool|string
 */
function hide_str($string, $bengin = 0, $len = 4, $type = 0, $glue = "@", $split = 0) {
    if (empty($string))
        return false;
    $array = [];
    if ($type == 0 || $type == 1 || $type == 4) {
        $strlen = $length = mb_strlen($string);
        while ($strlen) {
            $array[] = mb_substr($string, 0, 1, "utf8");
            $string = mb_substr($string, 1, $strlen, "utf8");
            $strlen = mb_strlen($string);
        }
    }
    if ($type == 0) {
        for ($i = $bengin; $i < ($bengin + $len); $i++) {
            if (isset($array[$i]))
                $array[$i] = "*";
        }
        $string = implode("", $array);
    } else if ($type == 1) {
        $array = array_reverse($array);
        for ($i = $bengin; $i < ($bengin + $len); $i++) {
            if (isset($array[$i]))
                $array[$i] = "*";
        }
        $string = implode("", array_reverse($array));
    } else if ($type == 2) {
        $array = explode($glue, $string);
        $array[0] = hide_str($array[0], $bengin, $len, 1);
        $string = implode($glue, $array);
    } else if ($type == 3) {
        $array = explode($glue, $string);
        $array[1] = hide_str($array[1], $bengin, $len, 0);
        $string = implode($glue, $array);
    } else if ($type == 4) {
        $left = $bengin;
        $right = $len;
        $tem = [];
        for ($i = 0; $i < ($length - $right); $i++) {
            if (isset($array[$i]))
                $tem[] = $i >= $left ? "*" : $array[$i];
        }
        $array = array_chunk(array_reverse($array), $right);
        $array = array_reverse($array[0]);
        for ($i = 0; $i < $right; $i++) {
            $tem[] = $array[$i];
        }
        $string = implode("", $tem);
    }
    if ($split) {
        $array = str_split($string, 4);
        $string = implode($glue, $array);
    }
    return $string;
}

/**
 * 日志写入
 * @param $msg
 * @param string $type
 * @return bool
 */
function dux_log($msg, $type = 'log') {
    return \dux\Dux::log($msg, $type);
}


/**
 * 浏览器日志
 * @param $msg
 * @param string $type
 * @param string $color
 * @return bool
 */
function browser_log($msg) {
    return \dux\Dux::browserLog($msg);
}

/**
 * 时间格式化
 * @param $time
 * @return string
 */
function date_tran($time) {
    $agoTime = (int)$time;

    // 计算出当前日期时间到之前的日期时间的毫秒数，以便进行下一步的计算
    $time = time() - $agoTime;

    if ($time >= 31104000) { // N年前
        $num = (int)($time / 31104000);

        return $num . '年前';
    }
    if ($time >= 2592000) { // N月前
        $num = (int)($time / 2592000);

        return $num . '月前';
    }
    if ($time >= 86400) { // N天前
        $num = (int)($time / 86400);

        return $num . '天前';
    }
    if ($time >= 3600) { // N小时前
        $num = (int)($time / 3600);

        return $num . '小时前';
    }
    if ($time > 60) { // N分钟前
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
function html_in($html) {
    return \dux\lib\Str::htmlIn($html);

}

/**
 * HTML反转义
 * @param $str
 * @return string
 */
function html_out($str) {
    return \dux\lib\Str::htmlOut($str);
}

/**
 * 清理HTML代码
 * @param $str
 * @return string
 */
function html_clear($str) {
    return strip_tags(\dux\lib\Str::htmlClear($str));
}

/**
 * 文本转html
 * @param $str
 * @return mixed
 */
function str_html($str) {
    $str = str_replace("\n", '<br>', $str);
    return $str;
}

/**
 * 字符串截取
 * @param $str
 * @param int $len
 * @param bool $suffix
 * @return string
 */
function str_len($str, $len = 20, $suffix = true) {
    return \dux\lib\Str::strLen($str, $len, $suffix);
}

/**
 * 格式化为数字(忽略限制)
 * @param $str
 * @return int|mixed
 */
function int_format($str) {
    return \dux\lib\Str::intFormat($str);
}

/**
 * 价格格式化不带千分位
 * @param $money
 * @return string
 */
function price_format($money) {
    return \dux\lib\Str::priceFormat($money);
}

/**
 * 价格计算
 * @param $n1
 * @param $symbol
 * @param $n2
 * @param string $scale
 * @return int|string
 */
function price_calculate($n1, $symbol, $n2, $scale = '2') {
    return \dux\lib\Str::priceCalculate($n1, $symbol, $n2, $scale);
}

/**
 * 指定位置插入字符串
 * @param $str
 * @param $i
 * @param $substr
 * @return string
 */
function str_insert($str, $i, $substr) {
    $startstr = '';
    for ($j = 0; $j < $i; $j++) {
        $startstr .= $str[$j];
    }
    $laststr = '';
    for ($j = $i; $j < strlen($str); $j++) {
        $laststr .= $str[$j];
    }
    $str = ($startstr . $substr . $laststr);
    return $str;
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
    if($cssLoad) {
        $data[] = '<link rel="stylesheet" href="' . $css . '">'."\r\n";
    }
    $data[] = '<script type="text/javascript" src="' . $js . '" data-cfg-autoload="false" data-debug="'.($config['debug_browser'] ? true : false).'" data-path="' . $path . '/" data-role="' . ROLE_NAME . '" data-root="' . ROOT_URL . '"></script>'."\r\n";
    return join("", $data);
}

/**
 * 常用js库
 * @param string $name
 * @return mixed
 */
function load_js($name = 'jquery') {
    $data = [
        'jquery' => '//cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js',
        'vue' => '//cdnjs.cloudflare.com/ajax/libs/vue/2.5.16/vue.min.js'
    ];
    $nameArray = explode(',', $name);
    $returnData = [];
    foreach ($nameArray as $vo) {
        $returnData[] = '<script type="text/javascript" src="' . $data[$vo] . '"></script>'."\r\n";
    }
    return join("", $returnData);
}
