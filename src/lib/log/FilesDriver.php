<?php

/**
 * 文件 存储日志
 */

namespace dux\lib\log;

class FilesDriver implements LogInterface {

    protected $driver = NULL;

    public function __construct(){
        $this->driver = $this;
    }

    public function log($msg, $type = 'INFO', $fileName = ''){

        $dir = DATA_PATH . 'log/';
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                error_log("Dir '{$dir}' Creation Failed");
                return false;
            }
        }

        if (empty($fileName)) {
            $file = $dir . date('Y-m-d') . '.log';
        } else {
            $file = $dir . $fileName . '.log';
        }
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }

        if (!error_log($type . ' ' . date('Y-m-d H:i:s') . ' ' . $msg . "\r\n", 3, $file)) {
            error_log("File '{$file}' Write failure");
        }

        return true;
    }

    public function obj(){
        return $this->driver;
    }

}