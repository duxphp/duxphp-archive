<?php

/**
 * 日志文件处理
 * @author: TS
 */
namespace dux\lib\log;


/**
 * 兼容抽象类
 * Class BaseLogMongoDbModel
 * @package dux\lib\log
 */
abstract class BaseLogMongoDbModel extends \dux\kernel\ModelMongoDb{

    protected $error = 'error';
    /**
     * 主键
     * @var null
     */
    private $primary = '_id';

    /**
     * 获取主键
     * @return string
     */
    public function getPrimary() {
        return $this->primary;
    }

    /**
     * 失败返回
     * @param $msg
     * @return bool
     */
    protected function error($msg = '') {
        if(!empty($msg)){
            $this->error = $msg;
        }
        return false;
    }

    /**
     * 成功返回
     * @param bool $data
     * @return bool
     */
    protected function success($data = true) {
        return $data;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getError() {
        return $this->error;
    }

}


/**
 * 数据存储(详情)
 * Class ToolsLogDataModel
 */
class ToolsLogDataModel extends BaseLogMongoDbModel {

    protected $table = 'tools_system_log_data';

    /*############################# 字段操作start #############################*/

    /**
     * 字段类型
     * @return array
     */
    public function paramTypeFields(){

        return [
            'log_id'                 => 'string',
            'type'                   => 'string',
            'msg'                    => 'string',
            'create_time'            => 'int',
        ];
    }

    /**
     * 字段默认值
     * @return array
     */
    public function paramDefaultFields(){

        return [
            'log_id'            => 0,
            'type'              => 'INFO',
            'msg'               => '',
            'create_time'       => time(),
        ];
    }


    /*############################# 字段操作end #############################*/

}

/**
 * 调用层
 * Class LogMongoDbModel
 * @package dux\lib\log
 */
class LogMongoDbModel extends BaseLogMongoDbModel {


    protected $table = 'tools_system_log';

    /*############################# 字段操作start #############################*/

    protected $_module = null;

    /**
     * logData model
     * @var null
     */
    private $_logDataModel = null;

    public function logDataModel(){

        if(is_null($this->_logDataModel))
            $this->_logDataModel = new ToolsLogDataModel();

        return $this->_logDataModel;
    }

    /**
     * 获取(设置)module
     * @param null $module
     * @return null|string
     */
    public function module($module = null){

        if(!empty($module))
            $this->_module = $module;

        //after 后端 before 前端
        return !is_null($this->_module) ? $this->_module : 'after';
    }

    /**
     * 字段类型
     * @return array
     */
    public function paramTypeFields(){

        return [
            'name'                   => 'string',
            'file'                   => 'string',
            'module'                 => 'string',
            'create_time'            => 'int',
            'update_time'            => 'int',
        ];
    }

    /**
     * 字段默认值
     * @return array
     */
    public function paramDefaultFields(){

        return [
            'name'              => '',
            'file'              => '',
            'module'            => $this->module(),
            'create_time'       => time(),
            'update_time'       => time(),
        ];
    }


    /*############################# 字段操作end #############################*/


    public function log($msg = '', $type = 'INFO',$filePath = ''){

        $dir = DATA_PATH . 'log/';

        $file = '';

        $name = null;

        if(!empty($filePath)){

            $pathArr = pathinfo($filePath);

            $name = $pathArr['filename'];

            $startInt = strrpos($pathArr['dirname'],$dir);

            $file = $pathArr['dirname'];

            if($startInt !== false)
                $file = substr($file,$startInt);
        }

        if(empty($name))
            $name = date('Y-m-d');

        $where = [
            'file'          => $file,
            'module'        => $this->module(),
            'name'          => $name,
        ];

        $logInfo = $this->where($where)->field([$this->getPrimary()])->find();

        $logId = null;

        if(!empty($logInfo)){
            $logId = $logInfo[$this->getPrimary()];
            //更新时间
            $this->where([$this->getPrimary() => $logId])->data(['update_time' => time()])->update();
        }else{

            $data = [
                'file'          => $file,
                'module'        => $this->module(),
                'name'          => $name,
            ];

            $logId = $this->insert($data);
            if(!$logId)
                return false;
        }


        $logData = [
            'log_id'        => $logId,
            'type'          => $type,
            'msg'           => $msg
        ];

        $this->logDataModel()->insert($logData);

        return true;
    }


    /**
     * 获取日期data
     * @param $logId
     * @return array|bool
     */
    public function getLogData($logId){

        if(empty($logId))
            return [];

        $modelData = $this->logDataModel();

        return $modelData->where(['log_id' => $logId])->order('create_time desc')->select();
    }


    /**
     * 获取目录列表
     * @return mixed
     */
    public function getFileList(){

        $where = [
            'module'        => $this->module()
        ];

        $fileArr = $this->distinct($this->_getTable(),'file',$where);

        return $fileArr;
    }

}


