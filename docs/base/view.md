# 视图

## 说明

将html内容和html内的dux模板标签进行编译转换，视图类在框架默认控制器已进行实例化，可以通过控制器赋值，视图会自动完成渲染html模板内容并进行输出显示

## 配置

在"global.php"中配置模板信息

```php
return [
  // 模板配置
  'dux.tpl' => [
    'path' => ROOT_PATH,                     // 项目根路径
  	'cache' => ROOT_PATH . 'data/tpl',       // 模板缓存路径
  ]
  ...
];
```

## 方法

通过模板对象可以调用视图类方法，以下为常用方法说明

- 获取模板赋值变量

  参数：

  - $key：变量名

  ```php
  $this->assign($name, $value = NULL);
  ```

- 设置模板变量

  参数：

  - $key：变量名，或者批量赋值的二位数组
  - $value：赋值内容，变量名为字符串时的

  ```php
  $this->_getView()->set($key, $value = null);
  ```

- 检查模板变量

  参数：

  - $key：变量名

  ```php
  $this->_getView()->has($key);
  ```

- 清理模板变量

  参数：

  - $key：变量名，不指定为所有赋值变量

  ```php
  $this->_getView()->clear($key = null);
  ```

- 编译模板

  说明：将模板标签转换为 php 模板进行返回

  参数：

  - $filePath：模板路径，模板绝对路径
  - $data：模板赋值数组

  ```php
  $this->_getView()->compile($filePath, $data = null);
  ```

- 输出模板

  说明：将模板编译后并且进行输出

  参数：

  - $file：模板路径，模板绝对路径
  - $data：模板赋值数组

  ```php
  $this->_getView()->render($file, $data = null);
  ```

- 模板渲染

  说明：将模板编译后并进行返回

  参数：

  - $file：模板路径，模板绝对路径
  - $data：模板赋值数组

  ```php
  $this->_getView()->fetch($file, $data = null);
  ```

- 自定义标签

  说明：自定义模板标签语法，在回调函数内处理新增模板标签并返回二位数组，键为标签可以使用正则表达式，值为转后内容或 php 标签

  参数：

  - $callback：回调函数

  ```php
  $this->_getView()->addTag(callable $callback);
  ```

  演示：

  ```php
  /**
   * 自定义标签
   *
   * $ltag string 多行标签前缀
   * $rtag string 多行标签后缀
   * $ldel string 短标签前缀
   * $rdel string 短标签后缀
   */
  $this->_getView()->addTag(function($ltag, $rtag, $ldel, $rdel) {
    return [
    		// 模板内标签为 {dux} 前台渲染为 测试标签
        '$ldel' . 'dux' . $rdel => '测试标签',
    ];
  });
  ```

  ## 标签语法

  框架自带一套标签，基本涵盖常用模板语法，标签采用类php语法与注释语法，让使用者无需记忆快速上手，标签语法分为短标签和多行标签，短标签默认使用"{}"包含，多行标签使用 "<!--标签-->" 注释符包含

  - 变量输出

    说明：将控制器赋值的模板变量进行输出，多维数组使用 "." 分割，类js变量

    示例：

    控制器赋值

    ```php
    $this->assign('var', 'dux');
    $this->assign('data', [
      'one' => 1,
      'tow' => 2,
    ]);
    ```

    模板内容

    ```html
    <h1>{$var}</h1>
    <ul>
      <li>{$data.one}</li>
      <li>{$data.tow}</li>
    </ul>
    ```

    html输出

    ```html
    <h1>dux</h1>
    <ul>
      <li>1</li>
      <li>2</li>
    </ul>
    ```

  - 逻辑判断

    说明：逻辑判断为多行标签，内部语法为标准 php 语法

    示例：

    控制器赋值

    ```php
    $this->assign('var', 2);
    ```

    模板内容

    ```html
    <!--if{$var > 5}-->
    大于5
    <!--{else}-->
    小于5
    <!--{/if}-->
    ```

    html输出

    ```
    小于5
    ```

  - Foreach 循环标签

    说明：循环标签为多行标签，内部为标准 php 语法，标签为 "foreach" 或 "loop"，标签为两种类型可以同时使用

    示例：

    控制器赋值

    ```php
    $this->assign('data', [
      [
        'id' => 1,
        'title' => '标题1’
      ],
      [
        'id' => 2,
        'title' => '标题2’
      ],
    ]);
    ```

    模板内容

    ```html
    <ul>
        <!--loop{$data as $key => $vo}-->
        <li>{$vo.title}</li>
        <!--{/loop}-->
    </ul>
    <ul>
        <li dux-loop="$data as $key => $vo">{$vo.title}</li>
    </ul>
    ```

    html输出

    ```html
    <ul>
      <li>标题1</li>
      <li>标题2</li>
    </ul>
    <ul>
      <li>标题1</li>
      <li>标题2</li>
    </ul>
    ```

  - For 循环标签

    说明：For 循环标签与 Foreach 循环标签使用方法一致，For 循环只可用"<!---->"标签

    示例：

    模板内容

    ```html
    <ul>
        <!--for{$i = 0; $i < 10; $i++}-->
        <li>{$i}</li>
        <!--{/for}-->
    </ul>
    ```

    html输出

    ```html
    <ul>
      <li>1</li>
      <li>2</li>
      <li>3</li>
      ...
    </ul>
    ```

  - 函数使用

    说明：您可以使用任何php自带函数和业务定义的函数，语法与php完全一致

    ```php
    {date('Y-m-d', $time)}
    ```

  - 属性标签

    说明：可以在某个 html 标签使用属性标签进行逻辑判断或输出

    示例：

    控制器赋值

    ```php
    $this->assign('var', true);
    ```

    模板内容

    ```html
    <input dux-attr="$var ? 'checked' : ''" type="radio">
    ```

    html输出

    ```html
    <input checked type="radio">
    ```

  - 注释标签

    可以对模板进行注释，注释内容并不会在渲染内容中进行输出

    ```html
    {#注释内容#}
    ```

  - 引入标签

    某些情况需要使用共用页面，可以使用该标签避免重复编写模板

    ```html
    <!--include{引入模板路径}-->
    ```

  - 常用变量

    模板封装一些常用变量标签可以直接在模板中使用

    ```html
    __PUBLIC__     <!--公共资源Url，不含结束"/"-->
    __ROOT__       <!--站点相对Url，不含结束"/"-->
    __APP__        <!--App目录，不含结束"/"-->
    ```

  - 自动渲染

    可以在模板内直接编写 scss 和 js，可以通过改标签进行自动转换css和压缩js，模板为预渲染为php语法，所以速度和原生标签无差

    ```html
    <style type="text/scss" dux-auto>
    .info {
      color: #333;
      p {
        color: #999;
      }
    }
    </style>
    <script dux-auto>
    var = 'dux';
    </script>
    ```

  - scss 转换标签

    可以在css内的某个地方使用scss标签

    ```html
    <style>
    h1 {
      font-size: 16px;
    }
    /* scss */
    .info {
      color: #333;
      p {
        color: #999;
      }
    }
    /* end scss */
    </style>
    ```

    js 压缩标签

    可以对js标签内某一块地方进行压缩转义

    ```html
    <script>
    var x = 'dux';
    //js-compress
    var test = function() {
      
    };
    //js-end
    </script>
    ```

    

    

