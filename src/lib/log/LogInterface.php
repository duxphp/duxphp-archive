<?php
/**
 * 存储驱动接口
 */
namespace dux\lib\log;

Interface LogInterface {


    /**
     * 日志写入
     * @param $msg
     * @param string $type
     * @param string $fileName
     * @return mixed
     */
    public function log($msg, $type = 'INFO', $fileName = '');
	
	/**
	 * 返回存储对象
	 * @return void
	 */
	public function obj();
		
}