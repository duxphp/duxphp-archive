<?php

class Config {

    protected static $config = [];

    public static function set($name, $value = null) {
        if(is_array($name)) {
            foreach($name as $key => $vo) {
                self::$config[$key] = $vo;
            }
        }else{
            if(is_array($value) && self::$config[$name]) {
                self::$config[$name] = array_merge(self::$config[$name], $value);
            }else {
                self::$config[$name] = $value;
            }

        }
    }

    public static function get($name = null) {
        if($name) {
            return isset(self::$config[$name]) ? self::$config[$name] : null;
        }else {
            return self::$config;
        }
    }

    public static function has($name) {
        return isset(self::$config[$name]);
    }

    public function clear($name) {
        unset(self::$config[$name]);
    }

}