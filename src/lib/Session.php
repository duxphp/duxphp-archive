<?php

namespace dux\lib;

/**
 * Session会话类
 * @author Mr.L <admin@duxphp.com>
 */

class Session {

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 驱动配置
     * @var string
     */
    protected $session = 'default';

    /**
     * 缓存对象
     * @var null
     */
    protected $object = null;

    /**
     * 缓存前缀
     * @var string
     */
    protected $pre = '';

    /**
     * 过期时间
     * @var int
     */
    protected $time = 30;

    /**
     * 实例化类
     * Session constructor.
     * @param string $pre
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config, string $pre = '') {
        $this->config = array_merge($this->config, $config);
        $this->pre = $pre;
        $this->time = ini_get('session.gc_maxlifetime');
        if (empty($this->config)) {
            throw new \Exception('Session config error', 500);
        }
        session_set_save_handler(
            [$this, '_open'],
            [$this, '_close'],
            [$this, '_read'],
            [$this, '_write'],
            [$this, '_destory'],
            [$this, '_clean']
        );
        if (!isset($_SESSION)) {
            session_start();
        }
    }

    /**
     * 开启session
     * @param $savePath
     * @param $sessionName
     * @return bool
     */
    public function _open($savePath, $sessionName) {
        if ($this->object) {
            return true;
        }
        $this->cache();
        return true;
    }

    /**
     * 关闭会话
     * @return mixed
     */
    public function _close() {
        $this->object = null;
        unset($this->object);
        return true;
    }

    /**
     * 读取会话
     * @param $sessionId
     * @return mixed
     */
    public function _read($sessionId) {
        try {
            $data = json_decode($this->cache()->get($this->pre . $sessionId), true);
            if (is_array($data)) {
                $data = json_encode($data);
            }
            return (string)$data;
        } catch (\Exception $e) {
            dux_log($e->getMessage());
            return '';
        }
    }

    /**
     * 写入会话
     * @param $sessionId
     * @param $sessionData
     * @return mixed
     */
    public function _write($sessionId, $sessionData) {
        try {
            $this->cache()->set($this->pre . $sessionId, json_encode($sessionData), $this->time);
            return true;
        } catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    /**
     * 销毁会话
     * @param $sessionId
     * @return bool
     */
    public function _destory($sessionId) {
        try {
            $this->cache()->del($this->pre . $sessionId);
            return true;
        } catch (\Exception $e) {
            dux_log($e->getMessage());
            return false;
        }
    }

    /**
     * 清理过期
     * @param $time
     * @return bool
     */
    public function _clean($time) {
        return true;
    }

    /**
     * 获取缓存对象
     * @return \dux\com\Cache
     * @throws \Exception
     */
    public function cache() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = \dux\Dux::cache('session', $this->config);
        return $this->object;
    }
}