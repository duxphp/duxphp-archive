<?php

/**
 * 公共Cli
 */

namespace dux\kernel;

class Cli {

    /**
     * Cli constructor.
     * @throws \Exception
     */
    public function __construct() {
        if (!IS_CLI) {
            throw new \Exception('Can only run on the command line', 500);
        }
    }

    /**
     * 输出消息
     * @param string $msg
     */
    public function echo(string $msg) {
        echo $msg;
    }

    /**
     * 返回消息
     * @param string $msg
     */
    public function return(string $msg) {
        exit($msg);
    }

}