<?php

/**
 * 公共控制器
 */

namespace dux\kernel;

class Controller {

    private $view;
    public $layout = NULL;

    /**
     * 实例化公共控制器
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
    public function assign($name, $value = NULL) {
        return $this->_getView()->set($name, $value);
    }

    /**
     * 模板输出
     * @param  string $tpl 模板名
     * @return mixed
     */
    public function display($tpl = '') {
        if (empty($tpl)) {
            $tpl = 'app/' . APP_NAME . '/view/' . LAYER_NAME . '/' . strtolower(MODULE_NAME) . '/' . strtolower(ACTION_NAME);
        }
        if ($this->layout) {
            $this->assign('layout', $this->_getView()->fetch($tpl));
            $tpl = $this->layout;
        }
        return $this->_getView()->render($tpl);
    }

    /**
     * 获取模板对象
     * @return object
     */
    protected function _getView() {
        if (!isset($this->view)) {
            $this->view = \Dux::view();
        }
        return $this->view;
    }

    /**
     * 页面跳转
     * @param  string $url 跳转地址
     * @param  integer $code 跳转代码
     * @return void
     */
    public function redirect($url, $code = 302) {
        header('location:' . $url, true, $code);
        exit;
    }

    /**
     * JSON输出
     * @param array $data
     * @param string $callback
     * @param int $code
     */
    public function json($data = [], $callback = '', $code = 200) {
        if ($callback) {
            $info = ['data' => $data, 'callback' => $callback];
            \Dux::header($code, function() use ($info) {
                if(!headers_sent()) {
                    header('Content-Type: application/javascript;charset=utf-8;');
                }
                echo $info['callback'] . '(' . json_encode($info['data']) . ');';
            });
        } else {
            \Dux::header($code, function() use ($data) {
                if(!headers_sent()) {
                    header('Content-Type: application/json;charset=utf-8;');
                }
                echo json_encode($data);
            });
        }
    }

    /**
     * 成功提示方法
     * @param  string $msg 提示消息
     * @param  string $url 跳转URL
     */
    public function success($msg, $url = null) {
        if (isAjax()) {
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
    public function error($msg, $url = null, $code = 500) {
        if (isAjax()) {
            $data = [
                'code' => $code,
                'message' => $msg,
                'url' => $url
            ];
            $this->json($data, '', $code);
        } else {
            $this->alert($msg, $url);
        }
    }

    /**
     * 404页面输出
     */
    public function error404() {
        \Dux::notFound();
    }


    /**
     * 错误页面输出
     * @param $title
     * @param $content
     * @param $code
     */
    protected function errorPage($title, $content, $code = 503) {
        \Dux::errorPage($title, $content, $code);
    }

    /**
     * JS窗口提示
     * @param  string $msg 提示消息
     * @param  string $url 跳转URL
     * @param  string $charset 页面编码
     * @return void
     */
    public function alert($msg, $url = NULL, $charset = 'utf-8') {

        \Dux::header(200, function () use ($msg, $url, $charset) {
            header("Content-type: text/html; charset={$charset}");
            $alert_msg = "alert('$msg');";
            if (empty($url)) {
                $go_url = 'history.go(-1);';
            } else {
                $go_url = "window.location.href = '{$url}';";
            }

            echo "<script>$alert_msg $go_url window.postMessage('{\"event\": \"close\"}');</script>";
        });
    }


}