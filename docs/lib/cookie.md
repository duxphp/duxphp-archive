# Cookie类

封装常用cookie操作方法

## 配置

```php
return [
		dux.cookie' => [
        'default' => [
            'pre' => 'dux_',
            'expiration' => 3600,
        		'path' => '/',
        		'domain' => null,
        		'secure' => false,
        		'http_only' => false,
        ],
    ],
    ...
];
```

## 使用

```php
// 可使用多种配置信息
$cookie = new \dux\lib\Cookie('default');
```

## 方法

### 获取 cookie 值

- 参数：

  $name：cookie键名

  $default：默认值，如果为空返回默认值

```php
$cookie->get($name = null, $default = null);
```

### 设置 cookie 值

- 参数：

  $name：cookie键名

  $value：储存值

  $expiration：过期时间，单位：秒

  $path：有效路径

  $domain：作用域名

  $secure：是否只通过https传输

  $http_noly：开启后无法通过js脚本等获取

```php
$cookie->set($name, $value, $expiration = 0, $path = null, $domain = null, $secure = null, $http_only = null);
```

### 删除 cookie 值

```php
$cookie->del($name, $path = null, $domain = null, $secure = null, $http_only = null);
```

