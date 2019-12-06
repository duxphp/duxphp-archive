<?php

namespace dux\console;

Interface ConsoleInterface {

    /**
     * 默认命令
     * @param $param
     * @return mixed
     */
    public function default($param);

    /**
     * 命令定义
     * @return array
     */
    public function getDefine(): array;
}