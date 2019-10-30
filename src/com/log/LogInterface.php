<?php
/**
 * 日志驱动接口
 */
namespace dux\com\log;

Interface LogInterface {


    /**
     * 日志写入
     * @param $msg
     * @param string $type
     * @param string $fileName
     * @param string $group
     * @return mixed
     */
    public function set($msg, $type = 'INFO', $fileName = '', $group = '');
	
		
}