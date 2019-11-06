<?php

namespace dux\lib;

/**
 * Session会话类
 * @author Mr.L <admin@duxphp.com>
 */
class Vcode {

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
     * 验证码对象
     * @var null
     */
    protected $object = null;

    /**
     * 缓存对象
     * @var null
     */
    protected $cacheObject = null;


    /**
     * 实例化验证码类
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 获取验证码
     * @param int $width
     * @param int $height
     * @param int $expire
     * @param string $key
     * @param int $quality
     * @return array
     * @throws \Exception
     */
    public function get(int $width = 100, int $height = 50, int $expire = 120, string $key = '', int $quality = 90) {
        $build = $this->getObj()->build($width, $height);
        $image = $build->get($quality);
        $code = strtolower($build->getPhrase());
        $token = hash_hmac('sha1', $code, $key);
        $this->cache()->set($code, $token, $expire);
        return [
            'image' => base64_encode($image),
            'token' => $token,
        ];
    }

    /**
     * 验证验证码
     * @param string $code
     * @param string $token
     * @param string $key
     * @return bool
     * @throws \Exception
     */
    public function has(string $code, string $token, string $key = '') {
        if (empty($code) || empty($token)) {
            return false;
        }
        $code = strtolower($code);
        $tmpToken = $this->cache()->get($code);
        if ($token <> $tmpToken) {
            return false;
        }
        $this->cache()->del($code);
        return true;
    }

    /**
     * 获取缓存对象
     * @return Cache|null
     * @throws \Exception
     */
    public function cache() {
        if ($this->cacheObject) {
            return $this->cacheObject;
        }
        $this->cacheObject = \dux\Dux::cache('vcode', $this->config);
        return $this->cacheObject;
    }

    /**
     * 获取验证码对象
     * @return \dux\com\Cache
     * @throws \Exception
     */
    public function getObj() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = new \Gregwar\Captcha\CaptchaBuilder();
        return $this->object;
    }
}