<?php

namespace dux\lib;

/**
 * Cookie类
 *
 * @author  Mr.L <349865361@qq.com>
 */
class Cookie {

    /**
     * 默认配置
     * @var array
     */
    protected $config = array(
        'pre' => 'dux_',
        'expiration' => 3600,
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => false,
    );

    /**
     * 驱动配置
     */
    protected $cookie = 'cookie';

    /**
     * 实例化类
     * @param $config
     * @throws \Exception
     */
    public function __construct($cookie = 'cookie') {
        if( $cookie ){
            $this->cookie = $cookie;
        }
        $config = \Config::get('dux.cookie');
        $this->config = array_merge($this->config, $config[$this->cookie]);
    }

    /**
     * 获取cookie
     * @param null $name
     * @param null $default
     * @return mixed
     */
    public function get($name = null, $default = null) {
        if (empty($name)) {
            return $_COOKIE;
        } else {
            $name = $this->config['pre'] . $name;
        }
        $value = json_decode($_COOKIE[$name], true);
        if (empty($value)) {
            $value = $default;
        }
        return $value;
    }

    /**
     * 设置cookie
     * @param $name
     * @param $value
     * @param null $expiration
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param null $http_only
     * @return bool
     */
    public function set($name, $value, $expiration = 0, $path = null, $domain = null, $secure = null, $http_only = null) {
        $name = $this->config['pre'] . $name;
        $expiration = isset($expiration) ? $expiration : $this->config['expiration'];
        $path = isset($path) ? $path : $this->config['path'];
        $domain = isset($domain) ? $domain : $this->config['domain'];
        $secure = isset($secure) ? $secure : $this->config['secure'];
        $http_only = isset($http_only) ? $http_only : $this->config['http_only'];
        $expiration = $expiration > 0 ? $expiration + time() : 0;
        return setcookie($name, json_encode($value), intval($expiration), $path, $domain, $secure, $http_only);
    }

    /**
     * 删除cookie
     * @param $name
     * @param null $path
     * @param null $domain
     * @param null $secure
     * @param null $http_only
     * @return bool
     */
    public function del($name, $path = null, $domain = null, $secure = null, $http_only = null) {
        unset($_COOKIE[$name]);
        return static::set($name, '', time()-86400, $path, $domain, $secure, $http_only);
    }
}
