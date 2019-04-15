# 框架安装

1. 在项目目录通过composer进行安装最新版

```shell
composer require duxphp/duxphp
```

2. 项目根目录推荐按照以下结构创建目录与文件

```
├─ app                   // 应用模块
├─ data                  // 项目数据
│  ├─ config             // 配置文件
│  │  ├─ global.php      // 框架配置文件
│  ├─ log                // 日志文件
│  ├─ cache              // 本地缓存
│  ├─ storage            // 静态存储
├─ public                // 公共静态资源
├─ upload                // 上传目录
├─ index.php             // 入口文件
```

3. 在项目入口文件"index.php"定义根目录并且进行框架加载

```php
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', __DIR__) . '/');
}
require __DIR__ . '/vendor/autoload.php';
\dux\Start::run();
```

4. 配置环境伪静态规则

- nginx规则

```
if (!-e $request_filename) {
   rewrite  ^(.*)$  /index.php?$1  last;
   break;
}
```

- apache规则

```
RewriteEngine on
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php?$1 [QSA,PT,L]
```

- iis规则

```
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
<system.webServer> 
<rewrite>
<rules>
<rule name="rule 3S" stopProcessing="true">
<match url="^(.*)$" />
<conditi>
<add input="{REQUEST_FILENAME}" matchType="IsFile" ignoreCase="false" negate="true" />
<add input="{REQUEST_FILENAME}" matchType="IsDirectory" ignoreCase="false" negate="true" />
</conditi>
<action type="Rewrite" url="/index.php?{R:1}" appendQueryString="true" />
</rule>
</rules>
</rewrite>
</system.webServer>
</configuration>
```

5. 安装完成访问绑定域名如果为空白页面则安装完成，后面还需要进行公共应用编写进行框架配置等，其他操作请查看相关文档说明。