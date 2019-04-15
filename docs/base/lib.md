# 核心类

核心类为框架封装的常用方法类，该类库为简化部分扩展类调用避免多次加载与增加常用方法

- 实例化视图类

  - 说明：实例化 "\dux\kernel\View" 缓存类

  - 参数：

    $config：视图配置

  ```php
  \dux\Dux::view($config = []);
  ```

- 实例化缓存类

  - 说明：实例化 "\dux\lib\Cache" 缓存类

  - 参数：

    $configName：配置名称，缓存配置名称

    $group：缓存分区

  ```php
  \dux\Dux::cache($configName = 'default', $group = 0);
  ```

- 实例化存储类

  - 说明：实例化 "\dux\lib\Storage" 缓存类

  - 参数：

    $configName：配置名称，缓存配置名称

    $group：存储分区

  ```php
  \dux\Dux::storage($configName = 'default', $group = 0);
  ```

- 实例化 Cookie 类

  - 说明：实例化 "\dux\lib\Cookie" Cookie 类

  - 参数：

    $configName：配置名称，缓存配置名称

  ```php
  \dux\Dux::cookie($configName = 'default');
  ```

- 实例化 Session 类

  - 说明：实例化 "\dux\lib\Session" Session 类

  - 参数：

    $configName：配置名称，缓存配置名称

  ```php
  \dux\Dux::session($configName = 'default');
  ```

- 获取请求数据

  - 说明：获取常用get、post等form或json请求数据

  - 参数：

    $method：请求类型，默认为全部，可指定 get、post、input等常用类型

    $key：请求键

    $default：默认值，为空时的默认值

    $function：处理函数，将请求内容函数进行处理

  ```php
  \dux\Dux::request($method = '', $key = '', $default = '', $function = '');
  ```

- Url生成

  - 说明：生成指定模块方法的前台访问地址

  - 参数：

    $str：资源地址，请参考Url设计文档 规则如："应用名\控制器\方法" 或 "层名\应用名\控制器\方法"

    $params：Url参数，数组参数

    $domain：域名状态，生成的Url是否包含域名，否则为相对地址

    $ssl：SSL 状态，生成的Url是否为自适应Https协议，否则为Http协议

    $get：固定参数，生成的Url是否包含配置文件中的永久参数，否则不包含永久参数

  ```php
  \dux\Dux::url($str = '', $params = [], $domain = false, $ssl = true, $get = true);
  ```

  - 演示：

  ```
  // http://域名/article/content/info?id=1
  \dux\Dux::url('article/content/info', ['id' => 1], true);
  ```

  

- 实例化自定义应用类

  - 说明：该方法用于不同应用类之间的互相调用，默认为 app 目录下应用

  - 参数：

    $class：类名路径

    $layer：层名，mvc等层名

  ```php
  \dux\Dux::target($class, $layer = 'model');
  ```

  - 演示：

  ```php
  // new \app\article\model\content();
  \dux\Dux::target('article/content', 'model');
  ```

- 读取配置文件

  - 说明：读取指定配置文件

  - 参数：

    $file：指定配置文件的绝对路径，不含 ".php"

    $enforce：是否正常加载，为否读取失败将不会有错误提示

  ```php
  \dux\Dux::loadConfig($file, $enforce = true);
  ```

- 保存配置文件

  - 说明：保存指定配置文件

  - 参数：

    $file：指定配置文件的绝对路径，不含 ".php"

    $config：数组配置内容

  ```php
  \dux\Dux::saveConfig($file, $config);
  ```

- 载入指定类

  - 说明：载入指定路径的类

  - 参数：

    $file：类库绝对路径，不含 ".php"

  ```php
  \dux\Dux::import($file = '');
  ```

- 发送指定Header头数据

  - 说明：返回指定header状态码和内容

  - 参数：

    $code：状态码，header状态码

    $callback：回调，输出回调内容

  ```php
  \dux\Dux::header($code, callable $callback = null);
  ```

- 404页面

  不存在输出页面

  ```php
  \dux\Dux::notFound();
  ```

- 错误页面

  - 说明：输出指定错误信息页面

  - 参数：

    $title：错误消息标题

    $msg：错误消息内容

    $code：状态码，header状态码

  ```php
  \dux\Dux::errorPage($title, $msg, $code = 503);
  ```

- 运行时间

  框架开始到执行改方法的时长

  ```php
  \dux\Dux::runTime();
  ```

- 日志写入

  - 说明：写入指定内容到程序日志

  - 参数：

    $msg：日志内容，如果为数组会自动转换为 JSON 字符串

    $type：日志类型，可用 "INFO"、"WARN"、"DEBUG"、"ERROR"

    $fileName：日志文件名称，默认为 "年-月-日" 作为日志名

  ```php
  \dux\Dux::log($msg, $type = 'INFO', $fileName = '');
  ```

  

  

