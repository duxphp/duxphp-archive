# 数据过滤与验证

该类用于多个字段应用多种验证规则与多种过滤规则

## 使用

```php
$data = [
	'title' => '这是一个标题',
	'content' => '这是一个内容',
	'num' => 100
];
$filter = \dux\lib\Filter();
```



## 数据批量验证，使用系统错误提示

```php


$filter->verifyArray($data, [
  'title' => [
      'required' => ['不能为空'],
      'len' => ['请输入5~10个字符', '5,10'],
      'url' => ['请输入Url'],
      'email' => ['请输入邮箱'],
      'numeric' => ['请输入数字'],
      'int' => ['请输入整数'],
      'ip' => ['请输入IP地址'],
      'date' => ['请输入正确的日期'],
      'string' => ['请输入字符串'],
      'chinese' => ['请输入中文'],
      'phone' => ['请输入手机号码'],
      'tel' => ['请输入座机号码'],
      'card' => ['请输入银行卡号'],
      'zip' => ['请输入邮编'],
      'empty' => ['不能为空'],
      'regex' => ['自定义提示', '正则表达式'],
      'object' => ['对象验证提示', $this, 'methods'],
      'function' => ['函数验证提示', 'empty']
    ],
    ...
]);

```

## 数据单独验证，方法请参考批量验证规则

```php
// $str 待验证数据
// $params 验证参数
$filter->verify()->方法($str, $params);
```



## 数据过滤

```php
$filter->filterArray($data, [
  'title' => [
      'len' => '5,10',                   // 截取字符串 5~10 位
      'url',                             // 过滤 Url 链接
      'email',                           // 过滤邮箱地址
      'numeric',                         // 过滤数字
      'int',                             // 过滤整数
      'ip',                              // 过滤IP地址
      'time',                            // 时间转字符串
      'chinese',                         // 过滤非中文字符串
      'html',                            // 过滤 html 代码
      'string',                          // 过滤字符串，清除html 换行等
      'regex' => '',                     // 正则过滤，填写正则表达式
      'object' => [$this, 'methods'],    // 通过对象过滤
      'function' => ''                   // 通过函数过滤
    ]
]);
```


```

## 数据单独过滤，方法请参考批量过滤规则

```php
// $str 待过滤数据
// $params 过滤参数
$str = $filter->filter()->方法($str, $params);
```




