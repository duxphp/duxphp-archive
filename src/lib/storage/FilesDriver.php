<?php

/**
 * 文件存储驱动
 */

namespace dux\lib\storage;

class FilesDriver implements StorageInterface {

    protected $config = [];

    public function __construct($config = array(), $group = 0) {
        $this->config = array_merge([
            'path' => ROOT_PATH . 'data/storage/',
            'group' => 0,
            'deep' => 0,
        ], (array)$config);
        $this->config['group'] = intval($group);
    }

    public function get($key) {
        $file = $this->_getFilePath($key);
        if (!is_file($file)) {
            return false;
        }
        $content = @file_get_contents($file);
        if (empty($content)) return false;
        $expire = (int)substr($content, 13, 12);
        if($expire) {
            if (time() >= $expire) return false;
        }
        $md5Sign = substr($content, 25, 32);
        $content = substr($content, 57);
        if ($md5Sign != md5($content)) return false;
        return @unserialize($content);
    }


    public function set($key, $value, $expire = 0) {
        $value = serialize($value);
        $md5Sign = md5($value);
        if($expire) {
            $expire = time() + $expire;
        }else {
            $expire = '0000000000';
        }
        $content = '<?php exit;?>' . sprintf('%012d', $expire) . $md5Sign . $value;
        $file = $this->_getFilePath($key);
        if (!file_put_contents($file, $content, LOCK_EX)) {
            return false;
        } else {
            return $file;
        }
    }

    public function inc($key, $value = 1) {
        return $this->set($key, intval($this->get($key)) + intval($value));
    }

    public function des($key, $value = 1) {
        return $this->set($key, intval($this->get($key)) - intval($value));
    }

    public function del($key) {
        return @unlink($this->_getFilePath($key));
    }

    public function clear($dir = '') {
        if (empty($dir)) {
            $dir = $this->config['path'] . '/' . $this->config['group'] . '/';
            $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
        }
        if (!is_dir($dir)) return false;

        $handle = opendir($dir);
        while (($file = readdir($handle)) !== false) {
            if ('.' != $file && '..' != $file) {
                is_dir("$dir/$file") ? $this->clear("$dir/$file") : @unlink("$dir/$file");
            }
        }
        if (readdir($handle) == false) {
            closedir($handle);
            @rmdir($dir);
        }
    }

    private function _getFilePath($key) {
        $key = md5($key);

        $dir = $this->config['path'] . $this->config['group'] . '/';
        for ($i = 0; $i < $this->config['deep']; $i++) {
            $dir = $dir . substr($key, $i * 2, 2) . '/';
        }
        $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);

        if (!file_exists($dir)) {
            if (!@mkdir($dir, 0777, true)) {
                throw new \Exception("Can not create dir '{$dir}'", 500);
            }
        }
        if (!is_writable($dir)) @chmod($dir, 0777);

        return $dir . $key . '.php';
    }
}