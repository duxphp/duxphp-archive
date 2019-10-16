<?php

namespace dux\com;
/**
 * 依赖注入
 * @package dux
 */
class Di {

    private $injections = [];
    private $instantiations = [];

    public function set($name, $class, $share = false) {
        $this->del($name);
        if (!($class instanceof \Closure) && is_object($class)) {
            $this->instantiations[$name] = $class;
        } else {
            $this->injections[$name] = ["class" => $class, "share" => $share];
        }
    }

    public function get($name, $params = []) {
        if (isset($this->instantiations[$name])) {
            return $this->instantiations[$name];
        }
        if (!isset($this->injections[$name])) {
            return null;
        }
        $concrete = $this->injections[$name]['class'];
        $obj = null;
        if ($concrete instanceof \Closure) {
            $obj = call_user_func_array($concrete, $params);
        } elseif (is_string($concrete)) {
            if (empty($params)) {
                $obj = new $concrete;
            } else {
                $obj = (new \ReflectionClass($concrete))->newInstanceArgs($params);
            }
        }
        if ($this->injections[$name]['share'] == true && $obj) {
            $this->instantiations[$name] = $obj;
        }
        return $obj;
    }

    public function has($name) {
        return isset($this->injections[$name]) or isset($this->instantiations[$name]);
    }

    public function del($name) {
        unset($this->injections[$name], $this->instantiations[$name]);
    }
    
}