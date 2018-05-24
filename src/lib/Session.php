<?php

namespace dux\lib;

/**
 * Session会话类
 *
 * @author Mr.L <349865361@qq.com>
 */

class Session {

    /**
     * 配置
     * @var array
     */
    protected $config = array();

    /**
     * 驱动配置
     * @var string
     */
    protected $session = 'default';

    /**
     * 缓存对象
     * @var null
     */
    protected $cache = null;

    /**
     * 实例化类
     * @param $config
     * @throws \Exception
     */
    public function __construct($session = 'default') {
        if (!empty($session)) {
            $this->session = $session;
        }
        $config = \Config::get('dux.session');
        $this->config = $config[$this->session];

        if (empty($this->config)) {
            throw new \Exception($this->session . ' session config error', 500);
        }

        if (!isset($this->config['time']) || $this->config['time'] <= 0) {
            $this->config['time'] = ini_get('session.gc_maxlifetime');
        }

        if ($this->config['cache']) {
            session_set_save_handler(
                array($this, '_open'),
                array($this, '_close'),
                array($this, '_read'),
                array($this, '_write'),
                array($this, '_destory'),
                array($this, '_clean')
            );
        }


        if (!isset($_SESSION)) {
            session_start();
        }

    }

    /**
     * 设置配置
     * @param $config
     * @return $this
     */
    public function setConfig($config) {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    /**
     * 读取配置
     * @param $key
     * @return mixed
     */
    public function get($key) {
        return $_SESSION[$this->config['pre'] . $key];
    }


    /**
     * 设置会话
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value) {
        return $_SESSION[$this->config['pre'] . $key] = $value;
    }

    /**
     * 删除会话内容
     * @param $key
     */
    public function del($key) {
        unset($_SESSION[$this->config['pre'] . $key]);
    }

    /**
     * 清空会话内容
     */
    public function clear() {
        session_unset();
        session_destroy();
    }

    /**
     * 开启session
     * @param $savePath
     * @param $sessionName
     * @return bool
     */
    public function _open($savePath, $sessionName) {
        if ($this->cache) {
            return true;
        }
        $this->cache = \Dux::cache($this->config['cache']);
        return true;
    }

    /**
     * 关闭会话
     * @return mixed
     */
    public function _close() {
        $this->cache = null;
        unset($this->cache);
        return true;
    }

    /**
     * 读取会话
     * @param $sessionId
     * @return mixed
     */
    public function _read($sessionId) {
        $data = json_decode($this->cache->get($this->config['pre'] . $sessionId), true);
        if(is_array($data)) {
            $data = json_encode($data);
        }
        return (string)$data;
    }

    /**
     * 写入会话
     * @param $sessionId
     * @param $sessionData
     * @return mixed
     */
    public function _write($sessionId, $sessionData) {
        return $this->cache->set($this->config['pre'] . $sessionId, json_encode($sessionData), $this->config['time']) ? true : false;
    }

    /**
     * 销毁会话
     * @param $sessionId
     * @return bool
     */
    public function _destory($sessionId) {
        return $this->cache->del($this->config['pre'] . $sessionId) >= 1 ? true : false;
    }

    /**
     * 清理过期
     * @param $time
     * @return bool
     */
    public function _clean($time) {
        $this->cache->get('*');
        return true;
    }
}