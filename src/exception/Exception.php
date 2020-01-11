<?php
/**
 * 异常处理
 */

namespace dux\exception;

class Exception extends \Exception {

    public function __construct($message, $code = 0, $file = '', $line = '', $trace = []) {
        new \dux\exception\Handle($message, $code, $file ?: $this->getFile(), $line ?: $this->getLine(), $trace ?: $this->getTrace(), \dux\Config::get('dux.debug'), \dux\Config::get('dux.debug'), \dux\Config::get('dux.log'));
    }
}