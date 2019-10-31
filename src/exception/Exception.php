<?php
/**
 * 异常处理
 */

namespace dux\exception;

class Exception extends \Exception {

    public function __construct($message, $code = 0) {
        new \dux\exception\Handle($message, $code, $this->getFile(), $this->getLine(), $this->getTrace(), \dux\Config::get('dux.debug'), false, \dux\Config::get('dux.log'));
    }
}