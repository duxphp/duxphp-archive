<?php

/**
 * HTTP访问类
 */

namespace dux\lib;


class Http {

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
    static public function doPost($url, $data = [], $timeout = 5, $header = "", $type = 'form') {
        try {
            $headers = self::header($header);
            $params = [];
            switch ($type) {
                case 'body':
                    $params['body'] = $data;
                case 'body':
                    $params['json'] = $data;
                case 'form':
                default:
                    $params['form_params'] = $data;
            }
            $response = self::getObj()->request('POST', $url, array_merge([
                'timeout' => $timeout,
                'http_errors' => false,
                'headers' => $headers
            ], $params));
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

    /**
     * 默认HTTP头
     * @return string
     */
    static private function defaultHeader() {
        $header = [];
        $header['User-Agent'] = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12';
        $header['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $header['Accept-language'] = 'zh-cn,zh;q=0.5';
        $header['Accept-Charset'] = 'GB2312,utf-8;q=0.7,*;q=0.7';
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