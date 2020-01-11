<?php
/**
 * 用户异常处理
 */

namespace dux\exception;

class Message extends \ErrorException {

    public function __construct($message, $code = 0) {
        new \dux\exception\Handle($message, $code, '', 0, [], false, true, false);
    }
}