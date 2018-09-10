<?php

/**
 * 又拍云上传驱动
 */

namespace dux\lib\upload;

class UpYunException extends \Exception {
    public function __construct($message, $code, \Exception $previous = null) {
        parent::__construct($message, $code);   // For PHP 5.2.x
    }
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
class UpYunAuthorizationException extends UpYunException {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, 401, $previous);
    }
}
class UpYunForbiddenException extends UpYunException {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, 403, $previous);
    }
}
class UpYunNotFoundException extends UpYunException {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, 404, $previous);
    }
}
class UpYunNotAcceptableException extends UpYunException {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, 406, $previous);
    }
}
class UpYunServiceUnavailable extends UpYunException {
    public function __construct($message, $code = 0, \Exception $previous = null) {
        parent::__construct($message, 503, $previous);
    }
}

class UpyunDriver implements UploadInterface {

    const VERSION            = '2.0';

/*{{{*/
    const ED_AUTO            = 'v0.api.upyun.com';
    const ED_TELECOM         = 'v1.api.upyun.com';
    const ED_CNC             = 'v2.api.upyun.com';
    const ED_CTT             = 'v3.api.upyun.com';

    const CONTENT_TYPE       = 'Content-Type';
    const CONTENT_MD5        = 'Content-MD5';
    const CONTENT_SECRET     = 'Content-Secret';

    // 缩略图
    const X_GMKERL_THUMBNAIL = 'x-gmkerl-thumbnail';
    const X_GMKERL_TYPE      = 'x-gmkerl-type';
    const X_GMKERL_VALUE     = 'x-gmkerl-value';
    const X_GMKERL_QUALITY   = 'x­gmkerl-quality';
    const X_GMKERL_UNSHARP   = 'x­gmkerl-unsharp';
/*}}}*/

    /**
     * @deprecated
     */
    private $_file_infos= NULL;

    private $_bucketname;
    private $_username;
    private $_password;
    private $_timeout = 3000;

    protected $endpoint;

    protected $config = [
        'bucket' => '',
        'username' => '',
        'password' => '',
        'domain' => '',
        'url' => ''

    ];
    protected $errorMsg = '';

    public function __construct($config = array()) {
        $this->config = array_merge($this->config, (array)$config['driverConfig']);

        $this->_bucketname = $this->config['bucket'];
        $this->_username = $this->config['username'];
        $this->_password = md5($this->config['password']);
        $this->_timeout = $timeout;
        $this->endpoint = "v0.api.upyun.com";
    }

    public function rootPath($path) {
        if(empty($this->config['username']) || empty($this->config['password']) || empty($this->config['bucket']) || empty($this->config['domain']) || empty($this->config['url'])) {
            $this->errorMsg = '请先配置又拍云上传参数！';
            return false;
        }
        return true;
    }

    public function checkPath($path) {
        return true;
    }

    public function saveFile($fileData) {
        
        // $uploadToken = $this->uploadToken();
        $name = $fileData['savename'];

        $path = "/upload/". $name;

        $file = @fopen($fileData['tmp_name'], 'rb');

        $data = $this->writeFile($path, $file, true);
        if (empty($data)) {
            $this->errorMsg = '图片服务器连接失败！';
            return false;
        }
    
        $fileData['url'] = $this->config['domain'] . $path;
        return $fileData;
    }


    public function getError() {
        return $this->errorMsg;
    }

    /**
     * 上传文件
     * @param string $path 存储路径
     * @param mixed $file 需要上传的文件，可以是文件流或者文件内容
     * @param boolean $auto_mkdir 自动创建目录
     * @param array $opts 可选参数
     */
    public function writeFile($path, $file, $auto_mkdir = False, $opts = NULL) {
        if (is_null($opts)) $opts = array();
        if (!is_null($this->_content_md5)) $opts[self::CONTENT_MD5] = $this->_content_md5;
        if (!is_null($this->_file_secret)) $opts[self::CONTENT_SECRET] = $this->_file_secret;
        if ($auto_mkdir === True) $opts['Mkdir'] = 'true';
        return $this->_do_request('PUT', $path, $opts, $file);
    }


    /**
    * 连接签名方法
    * @param $method 请求方式 {GET, POST, PUT, DELETE}
    * return 签名字符串
    */
    private function sign($method, $uri, $date, $length){
        $sign = "{$method}&{$uri}&{$date}&{$length}&{$this->_password}";
        return 'UpYun '.$this->_username.':'.md5($sign);
    }


    /**
     * HTTP REQUEST 封装
     * @param string $method HTTP REQUEST方法，包括PUT、POST、GET、OPTIONS、DELETE
     * @param string $path 除Bucketname之外的请求路径，包括get参数
     * @param array $headers 请求需要的特殊HTTP HEADERS
     * @param array $body 需要POST发送的数据
     * @param null $file_handle
     * @return mixed
     * @throws UpYunAuthorizationException
     * @throws UpYunException
     * @throws UpYunForbiddenException
     * @throws UpYunNotAcceptableException
     * @throws UpYunNotFoundException
     * @throws UpYunServiceUnavailable
     */
    protected function _do_request($method, $path, $headers = NULL, $body= NULL, $file_handle= NULL) {/*{{{*/
        $uri = "/{$this->_bucketname}{$path}";
        $ch = curl_init("http://{$this->endpoint}{$uri}");
        $_headers = array('Expect:');
        if (!is_null($headers) && is_array($headers)){
            foreach($headers as $k => $v) {
                array_push($_headers, "{$k}: {$v}");
            }
        }
        $length = 0;
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        if (!is_null($body)) {
            if(is_resource($body)){
                fseek($body, 0, SEEK_END);
                $length = ftell($body);
                fseek($body, 0);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_INFILE, $body);
                curl_setopt($ch, CURLOPT_INFILESIZE, $length);
            }
            else {
                $length = @strlen($body);
                array_push($_headers, "Content-Length: {$length}");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }
        else {
            array_push($_headers, "Content-Length: {$length}");
        }
        array_push($_headers, "Authorization: {$this->sign($method, $uri, $date, $length)}");
        array_push($_headers, "Date: {$date}");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $_headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        curl_setopt($ch, CURLOPT_POST, 1);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code == 0) throw new UpYunException('Connection Failed', $http_code);
        curl_close($ch);
        $header_string = '';
        $body = '';
        list($header_string, $body) = explode("\r\n\r\n", $response, 2);
        //var_dump($http_code);
        if ($http_code == 200) {
            if ($method == 'GET' && is_null($file_handle)) {
                return $body;
            }
            else {
                $data = $this->_getHeadersData($header_string);
                return count($data) > 0 ? $data : true;
            }
        }
        else {
            $message = $this->_getErrorMessage($header_string);
            if (is_null($message) && $method == 'GET' && is_resource($file_handle)) {
                $message = 'File Not Found';
            }
            $this->error = $message . ' code: '. $http_code;
            return false;
        }
    }/*}}}*/

    /**
     * 处理HTTP HEADERS中返回的自定义数据
     *
     * @param string $text header字符串
     *
     * @return array
     */
    private function _getHeadersData($text) {/*{{{*/
        $headers = explode("\r\n", $text);
        $items = array();
        foreach($headers as $header) {
            $header = trim($header);
            if(strpos($header, 'x-upyun') !== False){
                list($k, $v) = explode(':', $header);
                $items[trim($k)] = in_array(substr($k,8,5), array('width','heigh','frame')) ? intval($v) : trim($v);
            }
        }
        return $items;
    }/*}}}*/


    /**
     * 获取返回的错误信息
     *
     * @param string $header_string
     *
     * @return mixed
     */
    private function _getErrorMessage($header_string) {
        list($status, $stash) = explode("\r\n", $header_string, 2);
        list($v, $code, $message) = explode(" ", $status, 3);
        return $message;
    }

}