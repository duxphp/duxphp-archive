<?php

namespace dux;
/**
 * 配置参数
 * @package dux
 */
class Config {

    protected static $config = [
        'dux' => [
            'module_default' => 'controller',
            'module' => [
                'controller' => 'c',
                'cli' => 'x',
            ],
            'debug' => true,
            'debug_log' => true,
            'cache' => [
                'type' => 'files',
                'path' => ROOT_PATH . 'cache/tmp/',
                'group' => 'tmp',
            ],
            'tpl' => [
                'type' => 'files',
                'path' => ROOT_PATH . 'cache/tpl/',
                'group' => 'tmp',
            ],
            'session' => [
                'type' => 'files',
                'path' => DATA_PATH . 'cache/session/',
                'group' => 'tmp',
            ],
        ]
    ];

    protected static $cache = [];

    public static function set($name, $value = null) {
        if (is_array($name)) {
            self::$config = array_replace_recursive(self::$config, $name);
            self::$cache = [];
        } else {
            $segs = explode('.', $name);
            $segs = array_filter($segs);
            $data = &self::$config;
            foreach ($segs as $vo) {
                $data = &$data[$vo];
            }
            if (isset(self::$cache[$name])) {
                unset(self::$cache[$name]);
            }
            if (is_array($data) && is_array($value)) {
                $data = array_replace_recursive($data, $value);
            } else {
                $data = $value;
            }
            self::$cache[$name] = $data;
        }
    }

    public static function get($name = null, $default = null) {
        if (empty($name)) {
            return self::$config;
        }
        if (self::has($name)) {
            return self::$cache[$name];
        }
        return $default;
    }

    public static function has($name) {
        if (isset(self::$cache[$name])) {
            return true;
        }
        $segs = explode('.', $name);
        $segs = array_filter($segs);
        $data = self::$config;
        foreach ($segs as $vo) {
            if (array_key_exists($vo, $data)) {
                $data = $data[$vo];
                continue;
            } else {
                return false;
            }
        }
        self::$cache[$name] = $data;
        return true;
    }

    public static function clear($name) {
        self::set($name, null);
    }

    public static function load($config, $string = false) {
        if (!$string) {
            if (!is_file($config)) {
                throw new \dux\exception\Exception("Config file '{$config}' not found");
            }
            $config = file_get_contents($config);
        }
        $config = trim($config);
        if (substr($config, 0, 2) === '<?') {
            $config = '?>' . $config;
        }
        try {
            $data = eval($config);
        } catch (\Exception $e) {
            throw new \dux\exception\Exception("PHP string threw an exception");
        }
        if (is_callable($data)) {
            $data = call_user_func($data);
        }
        if (!is_array($data)) {
            throw new \dux\exception\Exception("PHP data does not return an array");
        }
        return $data;
    }

    public static function save($file, array $config = []) {
        $export = var_export($config, true);
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
                throw new \dux\exception\Exception("Directory [{$path['dirname']}] without permission");
            }
        }
        if (!file_put_contents($file, $export)) {
            throw new \dux\exception\Exception("Configuration file [{$file}] written to fail");
        }
        return true;
    }

}