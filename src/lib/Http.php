<?php

/**
 * HTTP访问类
 */

namespace dux\lib;

class Http {

    /**
     * GET请求
     * @param string $url
     * @param float $timeout
     * @param array $header
     * @param array $attr
     * @return mixed
     */
    static public function get(string $url, float $timeout = 5, array $header = [], array $attr = []) {
        return self::_request('GET', $url, [], $timeout, '', $header, $attr);
    }

    /**
     * POST数据
     * @param string $url
     * @param string $data
     * @param int $timeout
     * @param string $type
     * @param array $header
     * @param array $attr
     * @return mixed
     */
    static public function post(string $url, $data = '', int $timeout = 5, string $type = 'form', array $header = [], array $attr = []) {
        return self::_request('POST', $url, $data, $timeout, $type, $header, $attr);
    }

    /**
     * PUT请求
     * @param string $url
     * @param string $data
     * @param int $timeout
     * @param string $type
     * @param array $header
     * @param array $attr
     * @return mixed
     */
    static public function put(string $url, $data = '', int $timeout = 5, string $type = 'form', array $header = [], array $attr = []) {
        return self::_request('PUT', $url, $data, $timeout, $type, $header, $attr);
    }

    /**
     * DELETE请求
     * @param string $url
     * @param float $timeout
     * @param array $header
     * @param array $attr
     * @return mixed
     */
    static public function delete(string $url, float $timeout = 5, array $header = [], array $attr = []) {
        return self::_request('DELETE', $url, [], $timeout, '', $header, $attr);
    }

    /**
     * 请求封装
     * @param string $method
     * @param string $url
     * @param string $data
     * @param int $timeout
     * @param string $type
     * @param array $header
     * @param array $attr
     * @return mixed
     */
    static private function _request(string $method = 'POST', string $url, $data = '', int $timeout = 5, string $type = 'form', array $header = [], array $attr = []) {
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
        $attr = array_merge([
            'timeout' => $timeout,
        ], $params, $attr);
        return self::request($url, $method, $header, $attr);
    }

    /**
     * 通用请求
     * @param string $url
     * @param string $type
     * @param array $header
     * @param array $params
     * @return mixed
     */
    static function request(string $url, string $type = 'POST', array $header = [], array $params = []) {
        $data = array_merge([
            'headers' => $header
        ], $params);
        $response = self::getObj()->request($type, $url, $data);
        return $response->getBody()->getContents();
    }

    /**
     * 下载文件
     * @param mixed $filename 文件名
     * @param string $showname 显示文件名
     * @param integer $expire 缓存时间
     * @return boolean
     */
    static public function download($file, string $showname = '', int $expire = 1800) {
        if (is_string($file)) {
            $file = fopen($file, 'rb');
        }
        if (empty($file)) {
            throw new \Exception('File does not exist', 500);
        }
        header('Content-type:application/octet-stream; charset=utf-8');
        header("Content-Transfer-Encoding: binary");
        header("Accept-Ranges: bytes");
        header("Cache-control: max-age=" . $expire);
        header("Expires: " . gmdate("D, d M Y H:i:s", time() + $expire) . "GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . "GMT");
        header('Content-Disposition:attachment;filename="'.urlencode($showname).'"');
        fpassthru($file);
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