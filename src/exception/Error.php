<?php
/**
 * 异常处理
 */

namespace dux\exception;

class Error extends \ErrorException {

    public function __construct($message, $code = 0, $file = '', $line = '') {
        dux_log('Error');
        new \dux\exception\Handle($message, $code, $file ?: $this->getFile(), $line ?: $this->getLine(), [], \dux\Config::get('dux.debug'), false, \dux\Config::get('dux.log'));
    }
}