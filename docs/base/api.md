# 接口

## 说明

框架接口类封装常用数据返回，开发者可以根据需要进行继承使用 "\dux\kernel\Api"，为保证易用性建议只使用 "get" 与 "post" 方法

## 定义

典型的接口类如下：

```php
namespace app\index\index\api;

class IndexApi extends \dux\kernel\Api {

    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        $this->success('ok', []);
    }
}
```

框架会解析Url执行相应接口类：

```
http://域名/a/index/index/index
```

其中"a"为api层配置名称，可以通过配置进行更改，非默认层Url需要带入层名

## 继承方法

继承父类后可以使用以下的对象方法

- 成功提示

- 说明：成功后返回json数据

  参数：

  - $msg：消息，提示内容
  - $data：数据，成功后返回数组数据

  ```php
  $this->success($msg = '', array $data = []);
  ```

  输出：

  ```json
  {
    "code": 200,
    "message": "ok",
    "url": ''
  }
  ```

  - code：状态码，返回状态码，默认200
  - message：消息提示内容
  - result：返回数据

- 失败提示

  说明：失败后返回json数据，header信息统一返回200

  参数：

  - $msg：消息，提示内容
  - $code：状态码，输出状态码
  - $url：链接，失败后跳转链接

  ```php
  $this->error($msg = '', int $code = 500, string $url = '');
  ```

  输出：

  ```json
  {
    "code": 500,
    "message": "失败",
    "url": ''
  }
  ```

  - code：状态码，返回状态码，默认500
  - message：消息提示内容
  - url：转向url

- 404页面

  说明：返回404错误信息

  ```php
  $this->error404(string $msg = 'There is no data');
  ```

- 自定义数据输出

  说明：自定义数据格式与类型返回输出

  参数：

  $data：数据，输出数组数据

  $type：类型，数据输出类型可选json与jsonp

  ```php
  $this->returnData($data, string $type = 'json');
  ```

- Json数据输出

  说明：输出标准json数据

  参数：

  - $data：数据，输出数组数据
  - $charset：数据编码

  ```php
  $this->returnJson(array $data = [], string $charset = "utf-8");
  ```

- Jsonp数据输出

  说明：输出标准json数据

  参数：

  - $data：数据，输出数组数据
  - $q：回调变量，js回调变量名
  - $charset：数据编码

  ```php
  $this->returnJsonp(array $data = [], string $callback = 'q', string $charset = "utf-8");
  ```

  