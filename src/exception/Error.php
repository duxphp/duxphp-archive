<?php
/**
 * 异常处理
 */

namespace dux\exception;

class Error extends \ErrorException {

    public function __construct($message, $code = 0) {
        new \dux\exception\Handle($message, $code, $this->getFile(), $this->getLine(), $this->getTrace(), false, true, false);
    }
}