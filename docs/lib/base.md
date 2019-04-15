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
request($method = '', $key = '', $default = '', $function = '');
```

## Url生成

生成系统内的Url链接，该函数为核心类的封装函数

```php
url($str = '', $params = [], $domain = false, $ssl = true, $get = true);
```

## 实例化自定义应用类

该函数为核心类的封装函数

```php
target($class, $layer = 'model');
```

##配置文件加载

该函数为核心类的封装函数

```php
load_config($file, $enforce = true);
```

## 配置文件保存

该函数为核心类的封装函数

```php
save_config($file, $config);
```

## 二维数组排序

参数：

$data：需要排序的数组

$key：排序字段

$type：排序类型 "asc" 或 "desc"

```php
array_sort($data, $key, $type = 'asc');
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
list_dir($dir);
```

## 复制目录文件

复制目录下下所有文件到指定目录

```php
copy_dir($sourceDir, $aimDir);
```

## 删除目录

```php
del_dir($dir);
```

## 隐藏字符串

说明：隐藏字符中某个位置的指定字符串

参数：

$string：原始字符串

$bengin：隐藏开始位置，type = 4 时表示左侧保留长度

$len：隐藏长度，type = 4 时表示右侧保留长度

$type：隐藏类型，0 从左向右隐藏， 1 从右向左隐藏，2 从指定字符位置分割前由右向左隐藏，3 从指定字符位置分割后由左向右隐藏，4，保留首末指定字符串*

$glue：替换隐藏字符串的字符

$split：使用 glue 分割字符串，如：每4位进行分割

```php
hide_str($string, $bengin = 0, $len = 4, $type = 0, $glue = "@", $split = 0);
```

## 日志写入

该函数为核心类的封装函数

```php
dux_log($msg, $type = 'INFO');
```

## 个性化时间格式化

xx秒、xx分钟、xx天前时间戳格式化

```php
date_tran($time);
```

##Html 转义

```php
html_in($html);
```

## Html反转义

```php
html_out($str);
```

##Html清理

```php
html_clear($str);
```

##文本换行转Html

```php
str_html($str);
```

##字符串截取

```php
str_len($str, $len = 20, $suffix = true);
```

## 格式化为数字

```php
int_format($str);
```

## 价格格式化

```php
price_format($money);
```

##价格计算

- 参数：

  $n1：价格1

  $symbol：运算符，常用 "+"、"-"、"*"、"\"

  $n2：价格2

  $scale：保留小数

```php
price_calculate($n1, $symbol, $n2, $scale = '2');
```

## 指定位置插入字符串

- 参数：

  $str：原始字符串

  $i：从左到右指定位置

  $substr：要插入的字符串

```php
str_insert($str, $i, $substr);
```

## 生成不重复编号

```php
log_no($pre = '');
```

## 加载基础Url库

- 说明：需要项目引入Dux前端框架支持

- 参数：

  $path：项目根Url路径

  $cssLoad：是否加载Css

```php
load_ui($path = '', $cssLoad = true);
```

## 加载常用Js库

- 参数：

  $name：可选 "jquery"、"vue"

```php
load_js($name = 'jquery');
```

