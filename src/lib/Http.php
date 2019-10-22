<?php

/**
 * HTTP访问类
 */

namespace dux\lib;


class Http {

    static $error = '';

    /**
     * GET数据
     * @param string $url 访问地址
     * @param integer $timeout 超时秒
     * @param string $header 头信息
     * @return string
     */
    static public function doGet($url, $timeout = 5, $header = "") {
        try {
            $headers = self::header($header);
            $response = self::getObj()->request('GET', $url, [
                'timeout' => $timeout,
                'http_errors' => false,
                'headers' => $headers
            ]);
            $reason = $response->getReasonPhrase();
            $body = '';
            if ($reason == 'OK') {
                $body = $response->getBody()->getContents();
            }
            return $body;
        } catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    /**
     * POST数据
     * @param string $url 发送地址
     * @param array $data 发送数组
     * @param integer $timeout 超时秒
     * @param string $header 头信息
     * @return string
     */
    static public function doPost($url, $data = [], $timeout = 5, $header = "", $type = 'form', $attr = []) {
        try {
            $headers = self::header($header);
            $params = [];
            switch ($type) {
                case 'body':
                    $params['body'] = $data;
                    break;
                case 'json':
                    $params['json'] = $data;
                    break;
                case 'form':
                    $params['form_params'] = $data;
                    break;
            }
            $data = array_merge([
                'timeout' => $timeout,
                'http_errors' => false,
                'headers' => $headers
            ], $params, $attr);
            $response = self::getObj()->request('POST', $url, $data);
            $reason = $response->getReasonPhrase();
            if ($reason == 'OK') {
                return $response->getBody()->getContents();
            }
            return false;
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    static function request($url, $type = 'POST', $header = [], $params = []) {
        try {
            $headers = self::header($header);
            $data = array_merge([
                'timeout' => $timeout,
                'headers' => $headers
            ], $params);
            $response = self::getObj()->request($type, $url, $data);
            $reason = $response->getReasonPhrase();
            if ($reason == 'OK') {
                return $response->getBody()->getContents();
            }
            return false;
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            self::$error = $e->getMessage();
            return false;
        }
    }

    /**
     * 下载文件
     * @param string $filename 文件名
     * @param string $showname 显示文件名
     * @param integer $expire 缓存时间
     * @return boolean
     */
    static public function download($filename, $showname = '', $expire = 1800) {
        if (file_exists($filename) && is_file($filename)) {
            $length = filesize($filename);
        } else {
            die('下载文件不存在！');
        }
        $finfo = new \finfo(FILEINFO_MIME);
        $type = $finfo->file($filename);
        //发送Http Header信息 开始下载
        header("Pragma: public");
        header("Cache-control: max-age=" . $expire);
        //header('Cache-Control: no-appstore, no-cache, must-revalidate');
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
        header("Content-Disposition: attachment; filename=" . $showname);
        header("Content-Length: " . $length);
        header("Content-type: " . $type);
        header('Content-Encoding: none');
        header("Content-Transfer-Encoding: binary");
        readfile($filename);
        return true;
    }

    /**
     * 请求参数转换
     * @param $header
     * @return array
     */
    static private function header($header) {
        $headers = [];
        if (!empty($header)) {
            if (!is_array($header)) {
                $tmp = explode(':', $header, 2);
                $tmp = array_map(function ($str) {
                    return trim($str);
                }, $tmp);
                $headers[$tmp[0]] = $tmp[1];
            } else {
                foreach ($header as $key => $vo) {
                    if (is_int($key)) {
                        $vo = explode(':', $header, 2);
                        $tmp = array_map(function ($str) {
                            return trim($str);
                        }, $tmp);
                        $headers[$tmp[0]] = $tmp[1];
                    } else {
                        $headers[$key] = $vo;
                    }
                }
            }
        }
        $headers = $headers ? $headers : self::defaultHeader();
        return $headers;
    }

    static public function getError() {
        return self::$error;
    }

    /**
     * 默认HTTP头
     * @return string
     */
    static private function defaultHeader() {
        $header = [];
        return $header;
    }

    /**
     * 获取guzzle对象
     * @return mixed
     */
    static public function getObj() {
        $class = 'dux.guzzle';
        if (!di()->has($class)) {
            di()->set($class, '\GuzzleHttp\Client');
        }
        return di()->get($class);
    }

}