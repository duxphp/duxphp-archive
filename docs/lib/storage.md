# 数据存储类

将部分数据进行持久化保存，可进行正常的增删查改操作，该类使用 "redis"、"mongo"、"files" 等驱动方式

## 配置

将配置信息放置在 "data/config/global.php" 文件中

```php
return [
  'dux.storage' => [
  			// 存储类型可使用 "fiels"、"redis"、"mongo"
  			'default' => [
            'type' => 'files',
            'path' => DATA_PATH . 'cache/',
            'group' => 0,
            'deep' => 0,
        ],
        'redis' => [
            'type' => 'redis',
            'host' => '127.0.0.1',
            'port' => 6379,
            'group' => 0,
        ],
        'mongo' => [
            'type' => 'mongo',
            'host' => '127.0.0.1',
            'port' => 27017,
            'group' => 0,
        ],
  ]
  ...
];
```

## 使用

```php
// 缓存配置名，可以自定义配置
$configName = 'default';
// 存储分区，将易冲突数据放置在不同的分区下
$group = 0;
$storage = \dux\Dux::storage($configName, $group);
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

  $value：存储内容

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

### 返回驱动对象

返回实例化后的原始驱动对象

```php
$cache->obj();
```

