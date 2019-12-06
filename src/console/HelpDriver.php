<?php

/**
 * 帮助工具
 */

namespace dux\console;

class HelpDriver implements ConsoleInterface {


    public function getDefine(): array {
        return [
            'default' => '命令帮助'
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
            if (!$class instanceof \dux\console\ConsoleInterface) {
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


}