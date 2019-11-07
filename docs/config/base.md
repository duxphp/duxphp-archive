# 框架配置

自定不配置文件位于项目根目录下 "data/global.php" 目录，框架会自动进行加载，以下为系统默认配置

```php
return [
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
            'path' => ROOT_PATH . 'cache/tmp/',
            'securityKey' => 'data',
            'cacheFileExtension' => 'cache'
        ],
        'session' => [
            'type' => 'files',
            'path' => DATA_PATH . 'cache/session/',
            'securityKey' => 'data',
            'cacheFileExtension' => 'cache'
            ],
        ]
];
```

