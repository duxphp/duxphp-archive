<?php
/**
 * 异常处理
 */

namespace dux\exception;

class Error extends \ErrorException {

    public function __construct($message, $code = 0, $file = '', $line = '') {
        new \dux\exception\Handle($message, $code, $file ?: $this->getFile(), $line ?: $this->getLine(), [], \dux\Config::get('dux.debug'), \dux\Config::get('dux.debug'), \dux\Config::get('dux.log'));
    }
}