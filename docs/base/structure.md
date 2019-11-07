# 系统架构

## 说明

通过composer安装框架后框架则会在vendor目录下，您可以直接通过命名空间进行相关类库使用

## 命名空间

框架内所有类库的命名空间均以"\dux\类名"格式调用

## 框架常量

框架启动后会定义部分常量

```php
VERSION                   // 框架版本
VERSION_DATE              // 版本日期
IS_CLI                    // 是否CLI
URL                       // 当前Url
START_TIME                // 框架启动时间
DS                        // 目录分隔符
CORE_PATH                 // 框架绝对路径
DATA_PATH                 // data目录绝对路径
APP_PATH                  // app目录绝对路径
ROOT_URL                  // 项目目录相对路径
ROOT_SCRIPT               // 入口文件
DOMAIN                    // 访问域名，自适应访问协议
DOMAIN_HTTP               // 访问域名http协议
VIEW_LAYER_NAME           // 默认模块层名
ROLE_NAME                 // 路由层名
LAYER_NAME                // 当前模块层
APP_NAME                  // 当前模块名
MODULE_NAME               // 当前控制器或接口类名
ACTION_NAME               // 当前方法
```


## 框架结构

```
├─ com                                // 公共组件
│  ├─ log                             // 日志驱动
│  │  ├─ FilesDriver.php              // 文件驱动
│  │  ├─ LogInterface.php             // 日志接口类
│  │  ├─ MongoDriver.php              // Mongo驱动
│  │  └─ RedisDriver.php              // Redis驱动
│  ├─ Cache.php                       // 缓存类
│  ├─ Di.php                          // 依赖注入类
│  ├─ Log.php                         // 日志类
│  └─ Rotue.php                       // 路由类
├─ exception                          // 异常类
│  ├─ Error.php                       // 错误处理
│  ├─ Exception.php                   // 异常处理
│  └─ Handle.php                      // 异常基础
├─ kernel                             // 应用模块
│  ├─ model                           // 数据库驱动
│  │  ├─ DbInterface.php              // 驱动接口
│  │  └─ MysqlPdoDriver.php           // Mysql驱动
│  ├─ modelNo                         // NoSql驱动
│  │  ├─ ModelNoInterface.php         // 驱动接口
│  │  └─ MongoDriver.php              // MongoDB
│  ├─ Api.php                         // 基础接口类
│  ├─ Cli.php                         // 基础命令行类
│  ├─ Controller.php                  // 基础控制器类
│  ├─ Function.php                    // 常用函数
│  ├─ Model.php                       // 基础模型类
│  ├─ ModelExtend.php                 // 扩展模型类
│  ├─ ModelNo.php                     // NoSql类
│  └─ View.php                        // 视图类
├─ lib                                // 扩展类库
│  ├─ Category.php                    // 无限分类
│  ├─ Client.php                      // 客户端信息类
│  ├─ Filter.php                      // 数据验证过滤类
│  ├─ Http.php                        // 网络请求类
│  ├─ Image.php                       // 图片处理类
│  ├─ Session.php                     // Session类
│  ├─ Task.php                        // 任务类
│  ├─ Tools.php                       // 工具类
│  └─ Vcode.php                       // 图片验证码类
├─ tpl                                // 框架模板
│  └─ error.html                      // 错误页模板
├─ Config.php                         // 配置类
├─ Dux.php                            // 框架常用类
├─ Engine.php                         // 框架运行类
├─ Start.php                          // 框架启动类
```



