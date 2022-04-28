<?php

namespace dux\com;
/**
 * 依赖注入
 * @package dux
 */
class Di {

    private $registry = [];
    private $injections = [];

    /**
     * 设置依赖
     * @param string $name
     * @param string|\Closure $class
     */
    public function set(string $name, $class) {
        $this->del($name);
        if (!($class instanceof \Closure) && is_object($class)) {
            $this->injections[$name] = $class;
        } else {
            $this->registry[$name] = $class;
        }
    }

    /**
     * 获取依赖
     * @param string $name
     * @return object
     */
    public function get(string $name) {
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

    /**
     * 重载依赖
     * @param string $name
     * @param array $params
     * @return object
     * @throws \ReflectionException
     */
    public function make(string $name, array $params = []) {
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

    /**
     * 判断依赖
     * @param string $name
     * @return bool
     */
    public function has(string $name) {
        return isset($this->registry[$name]) || isset($this->injections[$name]);
    }

    /**
     * 删除依赖
     * @param string $name
     */
    public function del(string $name) {
        unset($this->registry[$name], $this->injections[$name]);
    }

}