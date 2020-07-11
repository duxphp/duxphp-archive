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
            'use' => [
                'safe_key' => 'dux',
                'pre' => 'dux_',
            ],
            'tpl' => [
            ],
            'log' => [
                'type' => \dux\com\log\FilesDriver::class,
                'path' => DATA_PATH . 'log/',
            ],
            'database' => [
                'type' => \dux\kernel\model\MysqlPdoDriver::class,
                'host' => 'localhost',
                'port' => '3306',
                'dbname' => 'dux',
                'username' => 'root',
                'password' => 'root',
                'prefix' => 'dux_',
                'charset' => 'utf8mb4',
            ],
            'cache' => [
                'type' => 'files',
            ],
            'session' => [
                'type' => 'files',
            ],
        ]
    ];

    protected static $cache = [];

    /**
     * 设置配置
     * @param $name
     * @param null $value
     */
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

    /**
     * 获取配置
     * @param null $name
     * @param null $default
     * @return array|mixed|null
     */
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

    /**
     * 清楚配置
     * @param $name
     */
    public static function clear($name) {
        self::set($name, null);
    }

    /**
     * 加载配置
     * @param $config
     * @param bool $string
     * @return mixed
     * @throws \Exception
     */
    public static function load($config, $string = false) {
        if (!$string) {
            if (!is_file($config)) {
                throw new \Exception("Config file '{$config}' not found");
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
            throw new \Exception("PHP string threw an exception");
        }
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
     * @param array $config
     * @return bool
     * @throws \Exception
     */
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
                throw new \Exception("Directory [{$path['dirname']}] without permission");
            }
        }
        if (!file_put_contents($file, $export)) {
            throw new \Exception("Configuration file [{$file}] written to fail");
        }
        return true;
    }

}