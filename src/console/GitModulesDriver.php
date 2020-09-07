<?php

/**
 * git子模块驱动
 */

namespace dux\console;

class GitModulesDriver implements ConsoleInterface {
    
    public function getDefine(): array {
        return [
            'default'   => 'git子模块服务',
            'add'       => '添加子模块',
            'init'      => '初始化',
            'pull'      => '更新',
            'reset'     => '强制更新',
            'remove'    => '解除关联',
            'removeAll' => '解除所有关联'
        ];
    }

    public function default($param) {
        $classAll = \dux\Console::$module;
        $data = [];
        foreach ($classAll as $key => $vo) {
            if (!class_exists($vo)) {
                continue;
            }
            $class = new $vo();
            if (!$class instanceof \dux\console\GitModulesDriver) {
                continue;
            }
            $data[] = [
                'name' => $key,
                'desc' => $class->getDefine()['default'],
            ];
            foreach ($class->getDefine() as $name => $desc) {
                if ($name == 'default') {
                    continue;
                }
                $data[] = [
                    'name' => $key . ':' . $name,
                    'desc' => $desc,
                ];
            }
        }
        $help = [];
        foreach ($data as $vo) {
            $help[] = str_pad($vo['name'], 30, ' ') . $vo['desc'];
        }
        return implode(PHP_EOL, $help);
    }

    /**
     * add命令
     * @param $param
     * @return string
     */
    public function add($param){
        $paramArray = explode('@', $param, 2);
        if(empty($paramArray)){
            return '请输入[url@path]参数';
        }

        if(count($paramArray) !=2){
            return 'add参数错误';
        }

        $shell = shell_exec("git submodule add {$paramArray[0]}} {$paramArray[1]}");
        if($shell) {
            return $shell;
        }
        return '';
    }

    /**
     * 初始化
     * @return string
     */
    public function init(){
        $shell = shell_exec('git submodule update --init --recursive');
        if($shell) {
            return $shell;
        }
        return '初始化成功';
    }

    /**
     * 更新
     * @return string
     */
    public function pull(){
        $shell = shell_exec('git submodule foreach git pull origin master');
        if($shell) {
            return $shell;
        }
        return '更新成功';
    }

    /**
     * 强制更新
     * @return string
     */
    public function reset(){
        $shell = shell_exec('git submodule foreach sudo git reset --hard origin/master');
        if($shell) {
            return $shell;
        }
        return '强制更新成功';
    }

    /**
     * 设置ini数据
     * @param $key
     * @param $data
     * @return string
     */
    private function setIniData($key,$data){
        $str = "[{$key}]";
        $str.= PHP_EOL;

        foreach ($data as $k => $v){
            $str.= ("	{$k} = {$v}" . PHP_EOL);
        }

        return rtrim($str,PHP_EOL);
    }

    /**
     * 设置模块公共方法
     * @param $filePath
     * @param string $module
     * @param string $submodule
     * @return array|string
     */
    private function setModulesFun($filePath,$module = '',$submodule = 'submodule'){

        $gitConfig = parse_ini_file($filePath,true,INI_SCANNER_RAW);

        if($gitConfig === false){
            return '读取配置失败';
        }

        $iniData = [];

        $delModule = [];

        foreach ($gitConfig as $key => $vo){

            $keyArr = explode(' ',$key);

            //查找匹配的 获取去除所有 submodule标签
            $sub = null;
            if(isset($keyArr[1])){
                //去除两侧符号
                $sub = trim($keyArr[1],'"');
            }

            if($keyArr[0] == $submodule && (empty($module) || $module == $sub)){
                $delModule[] = $sub;
                continue;
            }

            $iniData[] = $this->setIniData($key,$vo);
        }

        if(count($gitConfig) != count($iniData)){
            file_put_contents($filePath,implode(PHP_EOL,$iniData));
        }

        return $delModule;
    }

    /**
     * 设置模块
     * @param string $module
     * @return bool|string
     */
    private function setModules($module = ''){

        $delModule = [];

        //.gitmodules处理
        $gitFile = '.gitmodules';

        if(file_exists($gitFile)){
            $ret = $this->setModulesFun($gitFile,$module,'submodule');

            if(is_string($ret)){
                return $ret;
            }

            if(!filesize($gitFile)){
                @unlink($gitFile);
            }

            if(!empty($ret)){
                array_splice($delModule,count($delModule),0,$ret);
            }
        }

        //config处理
        $gitConfigFile = '.git/config';

        $ret = $this->setModulesFun($gitConfigFile,$module,'submodule');

        if(is_string($ret)){
            return $ret;
        }

        if(!empty($ret)){
            array_splice($delModule,count($delModule),0,$ret);
        }

        //去重
        $delModule = array_unique($delModule);

        //清除缓存
        foreach ($delModule as $vo){
            shell_exec("rm -f {$vo}/.git");//删除每个模块的git
            shell_exec('git rm -r --cached ' . $vo);//删除每个对应模块的缓存
            shell_exec('rm -rf .git/modules/' . $vo);//删除每个子模块的文件
        }

        return true;
    }

    /**
     * 解除模块关联
     * @param $param
     * @return bool|string
     */
    public function remove($param){
        if(empty($param)){
            return '请输入模块名';
        }

        $ret = $this->setModules($param);

        if($ret !== true){
            return $ret;
        }

        return "解除{$param}关联成功";
    }

    /**
     * 解除所有模块关联
     */
    public function removeAll(){

        $ret = $this->setModules();

        if($ret !== true){
            return $ret;
        }

        return '解除所有关联成功';
    }

}