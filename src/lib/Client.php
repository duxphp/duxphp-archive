<?php

namespace dux\lib;
/**
 * 客户端应用类
 */
class Client {

    /**
     * 获取语言
     * @return array
     */
    public static function getLang() {
        return (new \Jenssegers\Agent\Agent())->languages();
    }

    /**
     * 获取IP
     * @return string
     */
    public static function getIp() {
        if (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        }
        if (getenv('HTTP_X_REAL_IP')) {
            $ip = getenv('HTTP_X_REAL_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            $ips = explode(',', $ip);
            $ip = $ips[0];
        } else {
            $ip = '0.0.0.0';
        }
        return $ip;
    }

    /**
     * 获取来源地址
     * @return string
     */
    public static function getSource() {
        return htmlspecialchars($_SERVER['HTTP_REFERER']);
    }

    /**
     * 获取UA
     * @return string
     */
    public static function getUserAgent() {
        return htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 获取浏览器
     * @return string
     */
    public static function getBrowser() {
        return (new \Jenssegers\Agent\Agent())->browser();
    }

    /**
     * 获取操作系统
     * @return string
     */
    public static function getPlatform() {
        return (new \Jenssegers\Agent\Agent())->platform();
    }

    /**
     * 是否桌面平台
     * @return bool
     */
    public static function isDesktop() {
        return (new \Jenssegers\Agent\Agent())->isDesktop();
    }

    /**
     * 是否移动设备
     * @return bool
     */
    public static function isMobile() {
        return (new \Jenssegers\Agent\Agent())->isMobile();
    }

    /**
     * 是否平板设备
     * @return bool
     */
    public static function isTablet() {
        return (new \Jenssegers\Agent\Agent())->isTablet();
    }

}