# 入口文件

## 说明

1. DuxPHP采用单一入口模式，所有的访问操作必须经过入口文件
2. 框架执行流程具体由"Start.php"控制
3. 通过不同的Url进行区分不同应用模块，进行实例化和执行单一入口应用

## 使用

```php
\\ 定义根目录常量
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', str_replace('\\', '/', __DIR__) . '/');
}
\\ 加载引入composer类库
require __DIR__ . '/vendor/autoload.php';
\\ 启动运行框架
\dux\Start::run();
```



