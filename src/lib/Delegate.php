<?php

namespace dux\lib;

/**
 * 委托类
 */
class Delegate {

    /**
     * 成功委托
     * @var array
     */
    private $successDelegate = [];

    /**
     * 失败委托
     * @var array
     */
    private $errorDelegate = [];

    /**
     * 按入队顺序执行委托
     * @param array $delegateList
     * @return bool
     */
    private function execute(array &$delegateList){
        while ($delegate = array_shift($delegateList)){
            list($container,$params) = $delegate;
            //执行调用
            call_user_func_array($container,$params);
        }
        return true;
    }

    /**
     * 添加委托事件
     * @param array $delegateList
     * @param $container
     * @param array $params
     */
    private function addDelegate(array &$delegateList,$container,array $params = []){
        $delegateList[] = [$container, $params];
    }

    /**
     * 记录成功委托
     * @param $container
     * @param array $params
     * @return bool
     */
    public function successDelegate($container,array $params = []){
        $this->addDelegate($this->successDelegate,$container,$params);
        return true;
    }

    /**
     * 记录失败委托
     * @param $container
     * @param array $params
     * @return bool
     */
    public function errorDelegate($container,array $params = []){
        $this->addDelegate($this->errorDelegate,$container,$params);
        return true;
    }

    /**
     * 执行结束委托
     * @return bool
     */
    public function error(){
        return $this->execute($this->errorDelegate);
    }

    /**
     * 执行成功委托
     * @return bool
     */
    public function success(){
        return $this->execute($this->successDelegate);
    }

    /**
     * 释放 不执行
     */
    public function close(){
        $this->successDelegate = [];
        $this->errorDelegate = [];
    }

}