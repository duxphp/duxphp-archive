<?php

/**
 * 公共API
 */

namespace dux\kernel;

class Api {

    protected $data;

    /**
     * Api constructor.
     */
    public function __construct() {
        $request = request();
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $data = $data ? $data : [];
        $this->data = array_merge($request, $data);
    }

    /**
     * 返回成功数据
     * @param string $msg
     * @param array $data
     */
    public function success($msg = '', $data = []) {
        if (empty($msg)) {
            $msg = \Dux::$codes[200];
        }
        $data = [
            'code' => 200,
            'message' => $msg,
            'result' => $data
        ];
        \Dux::header(200, function () use ($data) {
            $this->returnData($data);
        });
    }

    /**
     * 返回错误数据
     * @param int $code
     * @param string $msg
     */
    public function error($msg = '', $code = 500) {
        if (empty($msg)) {
            $msg = \Dux::$codes[$code];
        }
        $data = [
            'code' => $code,
            'message' => $msg,
        ];
        \Dux::header($code, function () use ($data) {
            $this->returnData($data);
        });
    }

    /**
     * 数据不存在
     * @param string $msg
     */
    public function error404($msg = '记录不存在') {
        $this->error($msg, 404);
    }

    /**
     * 返回数据
     * @param $data
     * @param string $type
     */
    public function returnData($data, $type = 'json') {
        $format = request('', 'format');
        if (empty($format)) {
            $format = $type;
        }
        $callback = request('', 'callback');
        $format = strtolower($format);
        $charset = $this->data['charset'] ? $this->data['charset'] : 'utf-8';

        switch ($format) {
            case 'jsonp' :
                call_user_func_array([$this, 'return' . ucfirst($format)], [$data, $callback, $charset]);
                break;
            case 'json':
            default:
                call_user_func_array([$this, 'return' . ucfirst($format)], [$data, $charset]);
        }
    }

    /**
     * 返回JSON数据
     * @param array $data
     * @param string $charset
     */
    public function returnJson($data = [], $charset = "utf-8") {
        header("Content-Type: application/json; charset={$charset};");
        echo json_encode($data);
    }

    /**
     * 返回JSONP数据
     * @param array $data
     * @param string $callback
     */
    public function returnJsonp($data = [], $callback = 'q', $charset = "utf-8") {
        header("Content-Type: application/javascript; charset={$charset};");
        echo $callback . '(' . json_encode($data) . ');';
    }

}