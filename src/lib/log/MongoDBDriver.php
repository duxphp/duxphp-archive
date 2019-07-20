<?php

/**
 * mongoDb 存储日志
 */

namespace dux\lib\log;

class MongoDBDriver implements LogInterface {

    protected $driver = NULL;

    public function __construct(){
        $this->driver = new LogMongoDbModel();
    }

    public function log($msg, $type = 'INFO', $fileName = ''){

        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }

        return $this->driver->log($msg,$type,$fileName);
    }

    public function obj(){
        return $this->driver;
    }

}