<?php

namespace dux\console;

/**
 * Generate相关
 * Class IdeDriver
 * @package dux\console
 */
class GenerateDriver implements ConsoleInterface {

    public $define = [
        'meta' => '生成应用相关代码',
    ];


    public function getDefine(): array {
        return [
            'app' => '生成基础应用应用，参数为控应用名'
        ];
    }

    public function default($param) {
        return '请输入具体指令';
    }

    public function app($param) {
        fwrite(STDOUT, '应用名(英文小写)：');
        $app = trim(fgets(STDIN));
        fwrite(STDOUT, '应用名称(中英文名称)：');
        $name = trim(fgets(STDIN));
        fwrite(STDOUT, '应用描述(可选)：');
        $desc = trim(fgets(STDIN));
        fwrite(STDOUT, '应用作者(中英文名称)：');
        $auth = trim(fgets(STDIN));
        fwrite(STDOUT, '作者标识(英文小写)：');
        $authMark = trim(fgets(STDIN));
        fwrite(STDOUT, '是否系统应用(1或0)：');
        $system = trim(fgets(STDIN)) ? 1 : 0;
        fwrite(STDOUT, '应用中心管理(1或0)：');
        $manage = trim(fgets(STDIN)) ? 1 : 0;
        if (empty($app) || empty($name) || empty($auth) || empty($authMark)) {
            return $this->error('必要参数错误');
        }
        $appDir = './app/' . $app . '/';
        if (is_dir($appDir)) {
            return $this->error('应用已存在');
        }
        $md5 = md5($authMark . '.' . $app);
        $config = <<<EOF
<?php
return [
    'system' => $system,
    'manage' => $manage,
    'name' => '$name',
    'auth' => '$auth',
    'id' => '$md5',
    'desc' => '$desc',
    'package' => '$authMark.$app',
    'prefix' => 'dux_',
    'color' => '#000000'
];
EOF;
        if (!mkdir($appDir, 0777, true)) {
            return $this->error('应用目录创建失败');
        }
        mkdir($appDir . 'admin', 0777, true);
        mkdir($appDir . 'api', 0777, true);
        mkdir($appDir . 'config', 0777, true);
        mkdir($appDir . 'model', 0777, true);
        mkdir($appDir . 'service', 0777, true);
        mkdir($appDir . 'static', 0777, true);

        if(!file_put_contents($appDir . 'config/config.php', $config)) {
            return $this->error('配置文件创建失败');
        }

        return '模块生成成功';
    }

    public function error($msg) {
        return "\e[0;31m" . $msg;
    }


}