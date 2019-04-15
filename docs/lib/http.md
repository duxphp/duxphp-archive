# 网络请求类

封装常用 get、post 请求，自适应 curl、socket 请求方式，来源于 canphp

## get 请求

- 参数：

  $url：Url 地址

  $timeout：超时时间，单位：秒

  $header：附加 header 请求头信息

```php
\dux\lib\Http::doGet($url,$timeout=5,$header="");
```

## Post 请求

- 参数：

  $url：Url 地址

  $post_data：请求数组数据

  $timeout：超时时间，单位：秒

  $header：附加 header 请求头信息

```php
\dux\lib\Http::doPost($url, $post_data=array(), $timeout=5,$header="");
```

## 