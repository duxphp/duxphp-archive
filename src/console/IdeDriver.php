<?php

namespace dux\console;

/**
 * Ide相关
 * Class IdeDriver
 * @package dux\console
 */
class IdeDriver implements ConsoleInterface {

    public $define = [
        'meta' => '生成target方法索引提示',
    ];


    public function getDefine(): array {
        return [
            'meta' => '生成PhpStorm索引提示'
        ];
    }

    public function default($param) {
        return $this->meta($param);
    }

    public function meta($param) {
        $modelData = $this->parsingModule('model');
        $middleData = $this->parsingModule('middle');
        $serviceData = $this->parsingModule('service');
        $methods = [
            '\\model()' => $modelData,
            '\\middle()' => $middleData,
            '\\service()' => $serviceData,
        ];
        $data = [
            'methods' => $methods,
        ];
        $tpl = file_get_contents(__DIR__ . '/views/meta.php');
        ob_start();
        extract($data);
        eval('?>' . $tpl);
        $content = ob_get_clean();
        if(!file_put_contents('./.phpstorm.meta.php', $content)) {
            return 'Generate index failure';
        }
        return 'The index finished';
    }


    private function parsingModule($type) {
        $fileRule = './app/*/' . $type . '/*.php';
        $fileData = glob($fileRule);
        $modelData = [];
        foreach ($fileData as $vo) {
            $modelData[] = $this->parsingPath($vo, $type);
        }
        return $modelData;
    }

    private function parsingPath($info, $type) {
        $typeNum = strlen($type);
        $path = substr($info, 6, -4);
        $path = str_replace('\\', '/', $path);
        $pathArray = explode('/', $path);
        $app = $pathArray[0];
        $module = substr($pathArray[2], 0, -$typeNum);
        return [
            'name' => $app . '/' . $module,
            'class' => "app\\{$pathArray[0]}\\{$pathArray[1]}\\{$pathArray[2]}"
        ];
    }

}