<?php

/**
 * 公共控制器
 */

namespace dux\kernel;

class Controller {

    private $view;
    public $layout = null;

    /**
     * 初始化     控制器
     */
    public function __construct() {
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * 模板赋值
     * @param $name
     * @param null $value
     * @return mixed
     */
    public function assign($name, $value = null) {
        return $this->_getView()->set($name, $value);
    }

    /**
     * 模板输出
     * @param string $tpl 模板名
     * @return mixed
     */
    public function display(?string $tpl = null, $str = false) {
        if (empty($tpl)) {
            $tpl = 'app/' . APP_NAME . '/view/' . LAYER_NAME . '/' . strtolower(MODULE_NAME) . '/' . strtolower(ACTION_NAME);
        }
        if ($this->layout) {
            $this->assign('layout', $this->_getView()->fetch($tpl, [], $str));
            $tpl = $this->layout;
        }
        \dux\Dux::header(200, function () use ($tpl) {
            return $this->_getView()->fetch($tpl);
        }, [
            'Content-Type' => 'text/html;charset=utf-8;'
        ]);
    }

    /**
     * 获取模板对象
     * @return object
     */
    protected function _getView() {
        if (!isset($this->view)) {
            $this->view = \dux\Dux::view();
        }
        return $this->view;
    }

    /**
     * 页面跳转
     * @param string $url 跳转地址
     * @param integer $code 跳转代码
     * @return void
     */
    public function redirect(string $url, int $code = 302) {
        header('location:' . $url, true, $code);
        exit;
    }

    /**
     * JSON输出
     * @param array $data
     * @param string $callback
     * @param int $code
     */
    public function json(array $data = [], string $callback = '', int $code = 200) {
        if ($callback) {
            $info = ['data' => $data, 'callback' => $callback];
            \dux\Dux::header($code, function () use ($info) {
                return $info['callback'] . '(' . json_encode($info['data']) . ');';
            }, [
                'Content-Type' => 'application/javascript;charset=utf-8;'
            ]);
        } else {
            \dux\Dux::header($code, function () use ($data) {
                return json_encode($data);
            }, [
                'Content-Type' => 'application/json;charset=utf-8;'
            ]);
        }
    }

    /**
     * 成功提示方法
     * @param $msg 提示消息
     * @param string $url 跳转URL
     */
    public function success($msg, string $url = null) {
        if (isAjax() || is_array($msg)) {
            $data = [
                'code' => 200,
                'message' => $msg,
                'url' => $url
            ];
            $this->json($data);
        } else {
            $this->alert($msg, $url);
        }
    }

    /**
     * 失败提示方法
     * @param $msg
     * @param null $url
     * @param int $code
     */
    public function error(?string $msg, string $url = null, int $code = 500) {
        if (isAjax()) {
            $header = [
                'Content-Type' => 'application/javascript;charset=utf-8;'
            ];
            if ($url) {
                $header['Location'] = $url;
            }
            \dux\Dux::header($code, function () use ($msg) {
                return $msg;
            }, $header);
        } else {
            $this->alert($msg, $url);
        }
    }

    /**
     * 404页面输出
     */
    public function error404() {
        \dux\Dux::notFound();
    }


    /**
     * 错误页面输出
     * @param string $title
     * @param int $code
     */
    protected function errorPage(string $title, int $code = 503) {
        \dux\Dux::errorPage($title, $code);
    }

    /**
     * JS窗口提示
     * @param string $msg 提示消息
     * @param string $url 跳转URL
     * @param string $charset 页面编码
     * @return void
     */
    public function alert(string $msg, string $url = null, string $charset = 'utf-8') {
        \dux\Dux::header(200, function () use ($msg, $url) {
            $alert_msg = "alert('$msg');";
            if (empty($url)) {
                $go_url = 'history.go(-1);';
            } else {
                $go_url = "window.location.href = '{$url}';";
            }
            echo "<script>$alert_msg $go_url window.postMessage('{\"event\": \"close\"}');</script>";
        }, [
            'Content-type' => "text/html; charset={$charset}"
        ]);
    }


}
