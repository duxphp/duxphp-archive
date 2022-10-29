<?php

/**
 * 文件存储
 */

namespace dux\com\log;

use Exception;

class FilesDriver implements LogInterface
{

    protected $config = [
        'path' => ''
    ];

    public function __construct(array $config = [])
    {
        if ($config) {
            $this->config = $config;
        }
    }

    public function items($group = '')
    {
        $dir = $this->getDir($group);
        if (!$dir) {
            return [];
        }
        $files = glob($dir . '/*.log');
        $data = [];
        foreach ($files as $key => $vo) {
            $fileInfo = pathinfo($vo);
            $data[] = [
                'name' => $fileInfo['basename'],
            ];
        }
        return array_reverse($data);
    }

    public function get($name, $group = '')
    {
        $dir = $this->getDir($group);
        if (!$dir) {
            return [];
        }
        $file = file_get_contents($dir . '/' . $name);
        $tmp = array_reverse(explode("\n", $file));
        $data = [];
        foreach ($tmp as $key => $vo) {
            if (empty($vo)) {
                continue;
            }
            $info = explode(' ', $vo, 4);
            $data[] = [
                'time' => $info[1] . ' ' . $info[2],
                'level' => $info[0],
                'info' => $info[3]
            ];
        }
        return $data;
    }

    public function set($msg, $type = 'INFO', $name = '', $group = '') {
        $dir = $this->getDir($group);
        if (!$dir) {
            return false;
        }
        $file = $dir . '/' . $name . '.log';
        $msg = $type . ' ' . date('Y-m-d H:i:s') . ' ' . $msg . PHP_EOL;

        $isChmod = false;
        if(!file_exists($file)){
            $isChmod = true;
        }
        if (!error_log($msg, 3, $file)) {
            error_log("File '{$file}' Write failure");
        }
        if($isChmod){
            chmod($file,0777);
        }
        return true;
    }

    public function del($name = '', $group = '')
    {
        $dir = $this->getDir($group);
        if (!$dir) {
            return false;
        }
        $file = $dir . '/' . $name;
        return unlink($file);
    }

    public function clear($group = '')
    {
        $dir = $this->getDir($group);
        if (!$dir) {
            return false;
        }
        $files = glob($dir . '/*.log');
        foreach ($files as $key => $vo) {
            unlink($vo);
        }
        return true;
    }

    private function getDir($group = '')
    {
        $dir = $this->config['path'];
        $dir = str_replace('\\', '/', $dir);
        if (substr($dir, -1) <> '/') {
            $dir = $dir . '/';
        }
        if ($group) {
            $dir .= $group;
        }
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                return false;
            }
        }
        return $dir;
    }

}