<?php

namespace dux\lib;
/**
 * 客户端应用类
 */
class Client {
    /**
     * 获取客户端系统语言
     * @access public
     * @static true
     * @return string
     */
    public static function getUserLang() {
        return htmlspecialchars($_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    /**
     * 获取客户端IP
     *
     * @access public
     * @return string
     */
    public static function getUserIp() {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        return $ip;
    }

    /**
     * 获取当前页面的url来源
     *
     * @access public
     * @return string
     */
    public static function getUrlSource() {
        return htmlspecialchars($_SERVER['HTTP_REFERER']);
    }

    /**
     * 获取客户端浏览器信息.
     *
     * @access public
     * @return string
     */
    public static function getUserAgent() {
        return htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 获取客户端浏览器信息
     *
     * @access public
     * @return string
     */
    public static function getUserBrowser() {
        $userAgentInfo = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);

        if (strpos($userAgentInfo, 'MSIE 9.0')) {
            return 'IE9';
        } else if (strpos($userAgentInfo, 'MSIE 8.0')) {
            return 'IE8';
        } else if (strpos($userAgentInfo, 'MSIE 7.0')) {
            return 'IE7';
        } else if (strpos($userAgentInfo, 'MSIE 6.0')) {
            return 'IE6';
        } else if (strpos($userAgentInfo, 'Firefox')) {
            return 'Firfox';
        } else if (strpos($userAgentInfo, 'Chrome')) {
            return 'Chrome';
        } else if (strpos($userAgentInfo, 'Opera')) {
            return 'Opera';
        } else if (strpos($userAgentInfo, 'Safari')) {
            return 'Safari';
        } else if (strpos($userAgentInfo, 'Elinks')) {
            return 'Elinks';
        } else if (strpos($userAgentInfo, 'OmniWeb')) {
            return 'OmniWeb';
        } else if (strpos($userAgentInfo, 'Links')) {
            return 'Links';
        } else if (strpos($userAgentInfo, 'Lynx')) {
            return 'Lynx';
        } else if (strpos($userAgentInfo, 'Arora')) {
            return 'Arora';
        } else if (strpos($userAgentInfo, 'Epiphany')) {
            return 'Epiphany';
        } else if (strpos($userAgentInfo, 'Konqueror')) {
            return 'Konqueror';
        } else if (strpos($userAgentInfo, 'EudoraWeb')) {
            return 'EudoraWeb';
        } else if (strpos($userAgentInfo, 'Minimo')) {
            return 'Minimo';
        } else if (strpos($userAgentInfo, 'NetFront')) {
            return 'NetFront';
        } else if (strpos($userAgentInfo, 'POLARIS')) {
            return 'Polaris';
        } else if (strpos($userAgentInfo, 'BlackBerry')) {
            return 'BlackBerry';
        } else if (strpos($userAgentInfo, 'Nokia')) {
            return 'Nokia';
        } else {
            return 'Others';
        }
    }

    /**
     * 获取客户端操作系统信息     *
     * @access public
     * @return string
     */
    public static function getUserOs() {
        $userAgentInfo = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
        if (strpos($userAgentInfo, 'Windows NT 6.1')) {
            return 'Windows 7';
        } else if (strpos($userAgentInfo, 'Windows NT 6.0')) {
            return 'Windows Vista';
        } else if (strpos($userAgentInfo, 'Windows NT 5.2')) {
            return 'Windows 2003';
        } else if (strpos($userAgentInfo, 'Windows NT 5.1')) {
            return 'Windows XP';
        } else if (strpos($userAgentInfo, 'Windows NT 5.0')) {
            return 'Windows 2000';
        } else if (strpos($userAgentInfo, 'Windows ME')) {
            return 'Windows ME';
        } else if (strpos($userAgentInfo, 'PPC Mac OS X')) {
            return 'OS X PPC';
        } else if (strpos($userAgentInfo, 'Intel Mac OS X')) {
            return 'OS X Intel';
        } else if (strpos($userAgentInfo, 'Win98')) {
            return 'Windows 98';
        } else if (strpos($userAgentInfo, 'Win95')) {
            return 'Windows 95';
        } else if (strpos($userAgentInfo, 'WinNT4.0')) {
            return 'Windows NT4.0';
        } else if (strpos($userAgentInfo, 'Mac OS X Mach-O')) {
            return 'OS X Mach';
        } else if (strpos($userAgentInfo, 'Ubuntu')) {
            return 'Ubuntu';
        } else if (strpos($userAgentInfo, 'Debian')) {
            return 'Debian';
        } else if (strpos($userAgentInfo, 'AppleWebKit')) {
            return 'WebKit';
        } else if (strpos($userAgentInfo, 'Mint/8')) {
            return 'Mint 8';
        } else if (strpos($userAgentInfo, 'Minefield')) {
            return 'Minefield Alpha';
        } else if (strpos($userAgentInfo, 'gentoo')) {
            return 'Gentoo';
        } else if (strpos($userAgentInfo, 'Kubuntu')) {
            return 'Kubuntu';
        } else if (strpos($userAgentInfo, 'Slackware/13.0')) {
            return 'Slackware 13';
        } else if (strpos($userAgentInfo, 'Fedora')) {
            return 'Fedora';
        } else if (strpos($userAgentInfo, 'FreeBSD')) {
            return 'FreeBSD';
        } else if (strpos($userAgentInfo, 'SunOS')) {
            return 'SunOS';
        } else if (strpos($userAgentInfo, 'OpenBSD')) {
            return 'OpenBSD';
        } else if (strpos($userAgentInfo, 'NetBSD')) {
            return 'NetBSD';
        } else if (strpos($userAgentInfo, 'DragonFly')) {
            return 'DragonFly';
        } else if (strpos($userAgentInfo, 'IRIX')) {
            return 'IRIX';
        } else if (strpos($userAgentInfo, 'Windows CE')) {
            return 'Windows CE';
        } else if (strpos($userAgentInfo, 'PalmOS')) {
            return 'PalmOS';
        } else if (strpos($userAgentInfo, 'Linux')) {
            return 'Linux';
        } else if (strpos($userAgentInfo, 'DragonFly')) {
            return 'DragonFly';
        } else if (strpos($userAgentInfo, 'Android')) {
            return 'Android';
        } else if (strpos($userAgentInfo, 'Mac OS X')) {
            return 'Mac OS X';
        } else if (strpos($userAgentInfo, 'iPhone')) {
            return 'iPhone OS';
        } else if (strpos($userAgentInfo, 'Symbian OS')) {
            return 'Symbian';
        } else if (strpos($userAgentInfo, 'Symbian OS')) {
            return 'Symbian';
        } else if (strpos($userAgentInfo, 'SymbianOS')) {
            return 'SymbianOS';
        } else if (strpos($userAgentInfo, 'webOS')) {
            return 'webOS';
        } else if (strpos($userAgentInfo, 'PalmSource')) {
            return 'PalmSource';
        } else {
            return 'Others';
        }
    }
}
