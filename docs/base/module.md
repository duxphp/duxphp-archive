# 控制器

## 说明

控制器可以不需要继承基础类，基础类封装了常用的提示、模板输出等推荐父控制器进行继承 "\dux\kernel\Controller"

## 定义

典型的控制器如下：

```php
namespace app\index\index\controller;

class Index extends \dux\kernel\Controller {

		public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        echo 'index';
    }
}
```

框架会解析Url执行相应的控制器，示例Url如下：

```
http://域名/index/index/index
```

如果为index则为默认控制器，可以将index进行省略，命名空间为标准的以项目根目录为基础的命名空间

## 继承方法

继承父类后可以使用以下的对象方法

- 模板赋值

  说明：将内容赋值给模板变量

  参数：

  - $name：变量名，或者批量赋值的二位数组
  - $value：赋值内容，变量名为字符串时的

  ```php
  $this->assign($name, $value = NULL);
  ```

- 模板输出

  说明：进行模板渲染输出

  参数：

  - $tpl：模板路径，读取指定模板渲染，默认为自动路径

  ```php
  $this->display($tpl = '');
  ```

- 视图对象

  说明：获取视图对象，可以执行视图方法

  ```php
  $this->_getView();
  ```

- 页面跳转

  说明：页面跳转到指定Url

  参数：

  - $url：指定Url
  - $code: header状态码

  ```php
  $this->redirect($url, $code = 302);
  ```

- JSON输出

  说明：将指定数据转换成json或者jsonp进行输出

  参数：

  - $data：数组，需要输出的数据
  - $callback：回调方法，针对jsonp的回调方法
  - $code：状态码，header状态码

  ```php
  $this->json($data = [], $callback = '', $code = 200);
  ```

- 成功提示

  说明：操作成功后根据访问状态返回js提示或json数据

  参数：

  - $msg：消息，提示内容
  - $url：链接，成功后跳转链接

  ```php
  $this->success($msg, $url = null);
  ```

- 失败提示

  说明：操作失败后根据访问状态返回js提示或json数据

  参数：

  - $msg：消息，提示内容
  - $url：链接，失败后跳转链接
  - $code：状态码，header状态码

  ```php
  $this->error($msg, $url = null, $code = 500);
  ```

- 404页面

  说明：跳转页面到404

  ```php
  $this->error404();
  ```

- 错误页面

  说明：跳转到错误页面

  参数：

  $title：页面标题

  $content：提示内容

  $code：状态码，header状态码

  ```php
  $this->errorPage($title, $content, $code = 503);
  ```

- js弹窗提示

  说明：弹出js框进行消息提示

  参数：

  - $msg：提示内容
  - $url：跳转链接
  - $charset：页面编码

  ```php
  $this->alert($msg, $url = NULL, $charset = 'utf-8');
  ```

  