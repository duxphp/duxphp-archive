# 框架配置

配置文件位于项目根目录下 "data/global.php" 目录，框架会自动进行加载

```php
return [
	// 路由配置
  'dux.route' => [
        'params' => '',                           // Url永久参数，Url包含该参数会永久传递，多个参数使用","分割
   ],
   'dux.module_default' => 'controller',          // 默认访问层配置
   'dux.module' => [
        'controller' => 'c',                      // 模块层对应 Url 层名
        'api' => 'a',
        'admin' => 's',
        'mobile' => 'm',
    ],
    
    // 调试配置
    'dux.debug' => true,                          // 错误处理状态
    'dux.log' => true,                            // 错误日志记录
    
    // 用户配置
    'dux.use' => [
        'safe_key' => ''                          // 安全密钥，用于数据签名等加密密钥
        'cookie_pre' => 'dux_'                    // cookie session 前缀名
    ]
];
```

