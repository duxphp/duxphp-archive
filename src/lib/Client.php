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
        if ($_SERVER["HTTP_CLIENT_IP"] && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            if ($_SERVER["HTTP_X_FORWARDED_FOR"] && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                if ($_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
                    $ip = $_SERVER["REMOTE_ADDR"];
                } else {
                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],
                            "unknown")
                    ) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = "unknown";
                    }
                }
            }
        }
        return ($ip);
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