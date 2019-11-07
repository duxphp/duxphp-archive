# 数据缓存

用于部分临时数据的存储

## 配置

将配置信息放置在 "data/config/global.php" 文件中，"type"为缓存类型，其他为驱动配置参数,配置参数请参考 ``https://www.phpfastcache.com/``

```php
return [
    'dux' => [
        'cache' => [
            'type' => 'files',
            'path' => ROOT_PATH . 'cache/tmp/',
            'securityKey' => 'data',
            'cacheFileExtension' => 'cache'
        ],
    ],
  ...
];
```

## 使用

```php
// 缓存分组，将易冲突数据放置在不同的分组下
$group = 0;
// 缓存配置，可以自定义缓存配置
$config = [];
$cache = \dux\Dux::cache(string $group = 'default', array $config = []);
```

## 方法

### 获取数据

获取指定键名数据

```php
$cache->get($key);
```

### 设置数据

- 参数：

  $key：键名

  $value：缓存内容

  $expire：过期时间，单位：秒

```php
$cache->set($key, $value, $expire = 1800);
```

### 递增数据

- 说明：缓存内容为整数时可以通过该方法进行递增

- 参数：

  $key：键名

  $value：递增数量

```php
$cache->inc($key, $value = 1);
```

### 递减数据

- 说明：缓存内容为整数时可以通过该方法进行递减

- 参数：

  $key：键名

  $value：递减数量

```php
$cache->des($key, $value = 1);
```

### 删除数据

立即删除指定键名的数据

```php
$cache->del($key);
```

### 清除数据

清除指定分区所有数据

```php
$cache->clear();
```

