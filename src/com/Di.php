<?php

namespace dux\com;
/**
 * 依赖注入
 * @package dux
 */
class Di {

    private $registry = [];
    private $injections = [];

    public function set($name, $class) {
        $this->del($name);
        if (!($class instanceof \Closure) && is_object($class)) {
            $this->injections[$name] = $class;
        } else {
            $this->registry[$name] = $class;
        }
    }

    public function get($name) {
        if (isset($this->injections[$name])) {
            return $this->injections[$name];
        }
        if (!isset($this->registry[$name])) {
            return null;
        }
        $container = $this->registry[$name];
        $obj = null;
        if ($container instanceof \Closure) {
            $obj = call_user_func($container);
        } elseif (is_string($container)) {
            $obj = new $container;
        }
        if ($obj) {
            $this->injections[$name] = $obj;
        }
        return $obj;
    }

    public function make($name, $params = []) {
        if (!isset($this->registry[$name])) {
            return null;
        }
        $container = $this->registry[$name];
        $obj = null;
        if ($container instanceof \Closure) {
            $obj = call_user_func_array($container, $params);
        } elseif (is_string($container)) {
            if (empty($params)) {
                $obj = new $container;
            } else {
                $obj = (new \ReflectionClass($container))->newInstanceArgs($params);
            }
        }
        return $obj;
    }

    public function has($name) {
        return isset($this->registry[$name]) or isset($this->injections[$name]);
    }

    public function del($name) {
        unset($this->registry[$name], $this->injections[$name]);
    }

}