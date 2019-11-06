<?php

/**
 * 公共Cli
 */

namespace dux\kernel;

class Cli {

    /**
     * Api constructor.
     */
    public function __construct() {
        if (!IS_CLI) {
            throw new \Exception('Can only run on the command line', 500);
        }
    }

    /**
     * 输出消息
     * @param $msg
     */
    public function echo($msg) {
        echo $msg;
    }

    /**
     * 返回消息
     * @param $msg
     */
    public function return($msg) {
        exit($msg);
    }

}