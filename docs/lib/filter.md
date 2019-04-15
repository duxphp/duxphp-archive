# 数据过滤与验证

该类用于多个字段应用多种验证规则与多种过滤规则

## 使用

```php
$data = [
	'title' => '这是一个标题',
	'content' => '这是一个内容',
	'num' => 100
];
// data 为数据源，fields 为使用字段，留空使用所有字段
$filter = \dux\lib\Filter($data, $fields);
```



## 数据验证

```php
$status = $filter->validate([
  'title' => [
    [
      'len' => '5,10',                   // 验证字符串长度为 5~10 字符
      'required',                        // 验证是否为空
      'url',                             // 验证是否 Url
      'email',                           // 验证邮箱地址
      'numeric',                         // 验证数字
      'int',                             // 验证整数
      'ip',                              // 验证IP地址
      'date',                            // 验证日期，如：2019-01-01
      'string',                          // 验证字符串
      'chinese',                         // 验证中文
      'phone',                           // 验证手机号码
      'tel',                             // 验证座机号码
      'card',                            // 验证银行卡号
      'zip',                             // 验证邮编
      'empty',                           // 验证是否为空
      'regex' => '',                     // 验证正则，填写正则表达式
      'object' => [$this, 'methods'],    // 通过对象验证
      'image' => '',                     // 验证图片地址
      'function' => ''                   // 通过函数验证
    ],
    '标题']
]);

if(!$status) {
  echo $filter->getError();
}
```

## 数据过滤

```php
$filter->filter([
  'title' => [
    [
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
    ],
    '标题']
]);
```





