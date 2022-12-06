<?php

namespace dux\lib;

/**
 * 语言包
 */
class Lang {

    private $_translation = null;
    private $_lang = 'en_us';
    private $_data = [];
    private $_commonData = [];
    private $_totalData = [];
    private $_config = [];

    public function __construct(?string $lang = null,$config = [])
    {
        if(!is_null($lang)){
            $this->_lang = $lang;
        }
        $this->_config = array_merge($this->_config,$config);
        $this->init();
    }

    protected function getObj()
    {
        if(is_null($this->_translation)){
            $this->_translation = new \dux\lib\Translation($this->_config);
        }
        return $this->_translation;
    }

    /**
     * 路径
     * @return string
     */
    protected function path()
    {
        return sprintf('%slang/%s/%s',ROOT_PATH , $this->_lang, LAYER_NAME . '/' . APP_NAME . '.php');
    }

    /**
     * 初始化
     * @return void
     * @throws \Exception
     */
    protected function init()
    {
        $path = $this->path();
        if(file_exists($path)){
            $this->_data = $this->load($path);
        }

        $commonPath = sprintf('%slang/%s/%s',ROOT_PATH , $this->_lang, '/common.php');
        if(file_exists($commonPath)){
            $this->_commonData = $this->load($commonPath);
        }

        $this->mergeData();
    }

    /**
     * 合并data数据
     * @return array
     */
    private function mergeData()
    {
        $this->_totalData = $this->_commonData + $this->_data;
        return $this->_totalData;
    }

    /**
     * 获取data数据
     * @return array
     */
    public function data($name = 'totalData')
    {
        return $this->{'_' . $name} ?? [];
    }

    /**
     * 加载配置
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    private function load($path) {
        if (!is_file($path)) {
            throw new \Exception("lang file '{$path}' not found");
        }
        $data = require_once $path;
        if (is_callable($data)) {
            $data = call_user_func($data);
        }
        if (!is_array($data)) {
            throw new \Exception("PHP data does not return an array");
        }
        return $data;
    }

    /**
     * 保存配置到文件
     * @param $file
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    private function save($file, array $data = []) {
        $export = var_export($data, true);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        $export = "<?php" . PHP_EOL . "return " . $export . ";";

        $path = pathinfo($file, PATHINFO_DIRNAME);
        if (!is_dir($path)) {
            try {
                mkdir($path, 0777, true);
            } catch (\Exception $e) {
                throw new \Exception("Directory [{$path['dirname']}] without permission");
            }
        }
        if (!file_put_contents($file, $export)) {
            throw new \Exception("Translation file [{$file}] written to fail");
        }
        return true;
    }

    /**
     * 加载语言
     * @param string $str
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function lang(string $str)
    {
        if(is_null($str) || $str === ''){
            return $str;
        }
        if(!isset($this->_totalData[$str]) && !empty($this->_config)){
            try {
                $value = $this->getObj()->translation($str,$this->_lang);
                if($value === false){
                    return $str;
                }
                $this->_data[$str] = $value;
                $this->mergeData();
                $this->save($this->path(),$this->_data);
            }catch (\Exception $e){
                dux_log($e->getMessage(),'error');
                return $str;
            }
        }
        return $this->_totalData[$str] ?? $str;
    }

}