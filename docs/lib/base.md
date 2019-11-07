# 常用函数

常用函数在框架加载时进行自动载入，项目内可直接使用，无需手动引入

## 判断ajax请求

```php
isAjax()
```

## 判断get请求

```php
isGet()
```

## 判断post请求

```php
isPost()
```

## 判断APP请求

app请求头加入"from=app"参数即可判断

```php
isApp()
```

## 判断Api请求

hedaer请求头需要传递"token"参数即可判断

```php
isApi()
```

## 依赖注入类

```php
di()
```

## 获取系统钩子

- 说明：获取应用指定模块类的同一方法

- 参数：

  $layer：层名，应用内层名，如 "model"、"service" 层

  $name：钩子方法名

  $method：钩子类型

  $vars：方法参数，二维数组参数

  ```php
  hook($layer, $name, $method, $vars = [])
  ```

- 示例：

  钩子调用处使用，如：调用"service"层的管理权限

  ```php
  $data = hook('service', 'purview', 'admin', ['dux', 1]);
  ```

  在任意应用内的对应类写入钩子返回，如："\app\article\service\PurviewService.php"

  ```php
  
  namespace app\system\service;
  
  class PurviewService {
  
    public function getAdminPurview($name, $num) {
      // dux
      echo $name;
      // 1
      echo $num;
    	return [];
    }
    
   }
  ```

  "PurviewService"为指定的方法名的类名，"getAdminPurview" 为钩子返回方法 即"get 钩子类型 方法名"

## 执行系统钩子

- 说明：执行应用指定模块类的同一方法

- 参数：

  $layer：层名，应用内层名，如 "model"、"service" 层

  $name：钩子方法名

  $method：钩子类型

  $vars：方法参数，二维数组参数

  ```php
  run($layer, $name, $method, $vars = [])
  ```

## 获取请求参数

获取常用get、post等form或json请求数据，该函数为核心类的封装函数

```php
request(string $method = '', string $key = '', string $default = '', $function = null);
```

## Url生成

生成系统内的Url链接，该函数为核心类的封装函数

```php
url(string $str = '', array $params = [], bool $domain = false, bool $ssl = true);
```

## 实例化自定义应用类

该函数为核心类的封装函数

```php
target(string $class, string $layer = 'model');
```

##配置文件加载

该函数为核心类的封装函数

```php
load_config(string $file, $enforce = true);
```

## 配置文件保存

该函数为核心类的封装函数

```php
save_config(string $file, array $config);
```

## 二维数组排序

参数：

$data：需要排序的数组

$key：排序字段

$type：排序类型 "asc" 或 "desc"

```php
array_sort(array $data, $key, string $type = 'asc');
```

## 数据签名

将字符串或数组进行系统内部签名生成签名字符串

```php
data_sign($data);
```

## 验证签名

说明：将字符串或数组与生成的签名字符串进行验证返回 "bool"

参数：

$data：验证数据

$sign：签名字符串

```php
data_sign_has($data, $sign = '');
```

##Url Base64 编码

生成符合 Url 传递的 Base64 编码

```php
url_base64_encode($string);
```

##Url Base64 解码

```php
url_base64_decode($string);
```

## 遍历指定目录下文件

```php
list_dir(string $dir);
```

## 复制目录文件

复制目录下下所有文件到指定目录

```php
copy_dir(string $sourceDir, string $aimDir);
```

## 删除目录

```php
del_dir(string $dir);
```

## 隐藏字符串

说明：隐藏字符中某个位置的指定字符串

参数：

$string：原始字符串

$start：隐藏开始位置

$length：隐藏长度

$re：替换字符

```php
hide_str(string $string, int $start = 0, int $length = 0, string $re = '*');
```

## 日志写入

该函数为核心类的封装函数

```php
dux_log($msg = '', string $type = 'INFO', string $fileName = '');
```

## 个性化时间格式化

xx秒、xx分钟、xx天前时间戳格式化

```php
date_tran($time);
```

##Html 转义

```php
html_in(string $html = '');
```

## Html反转义

```php
html_out(string $str = '');
```

##Html清理

```php
html_clear(string $str = '');
```

##文本换行转Html

```php
str_html(string $str = '');
```

##字符串截取

```php
str_len(string $str, int $length = 20, bool $suffix = true);
```

## 格式化为数字

```php
int_format($str = 0);
```

## 价格格式化

```php
price_format($money = 0);
```

##价格计算

- 参数：

  $n1：价格1

  $symbol：运算符，常用 "+"、"-"、"*"、"\"

  $n2：价格2

  $scale：保留小数

```php
price_calculate($n1, string $symbol, $n2, int $scale = 2);
```

## 生成不重复编号

```php
log_no($pre = '');
```

## 对象转数组

```php
object_to_array($objList, $keyList = ['key', 'text']);
```

## MD转Html

```php
markdown_html(string $text, bool $line = false);
```

## 压缩js

```php
pack_js(string $str);
```

## 压缩js

```php
build_scss(string $str);
```


## 加载基础Url库

- 说明：需要项目引入Dux前端框架支持

- 参数：

  $path：项目根Url路径
  
  $cssLoad：是否加载Css

```php
load_ui(string $path = '', bool $cssLoad = true);
```

## 加载常用Js库

- 参数：

  $name：可选 "jquery"、"vue"

```php
load_js(string $name = 'jquery');
```

