# 网络请求类

封装常用 get、post 请求，自适应 curl、socket 请求方式，针对 `GuzzleHttp`进行二次封装

## get 请求

- 参数：

  $url：Url 地址

  $timeout：超时时间，单位：秒

  $header：header 请求头信息,如:"['x-dux' => 'duxphp']"
  
  $attr：附加参数 会合并到最终请求

```php
\dux\lib\Http::get(string $url, float $timeout = 5, array $header = [], array $attr = []);
```

## Post 请求

- 参数：

  $url：Url 地址

  $data：Post数据
  
  $timeout：超时时间，单位：秒

  $type：请求类型，可为 `form` `json` `body`，前两种类型的数据请传递数组
  
  $header：header 请求头信息,如:"['x-dux' => 'duxphp']"
  
  $attr：附加参数 会合并到最终请求

```php
\dux\lib\Http::post(string $url, $data = '', int $timeout = 5, string $type = 'form', array $header = [], array $attr = []);
```

## Put请求

```php
\dux\lib\Http::put(string $url, $data = '', int $timeout = 5, string $type = 'form', array $header = [], array $attr = []);
```

## Delete请求

```php
\dux\lib\Http::delete(string $url, float $timeout = 5, array $header = [], array $attr = []);
```

## 自定义请求

```php
\dux\lib\Http::request(string $url, string $type = 'POST', array $header = [], array $params = []);
```

## 自定义请求

```php
\dux\lib\Http::download($file, string $showname = '', int $expire = 1800);
```

## 获取 `GuzzleHttp\Client` 对象

```php
\dux\lib\Http::getObj();
```
