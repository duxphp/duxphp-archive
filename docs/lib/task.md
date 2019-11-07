# 队列任务类

该队列任务类采用Redis、pcntl开发的高效队列类

## 实例化

```php
// Redis配置
$config = [
    'host' => 'localhost',
    'port' => 6379,
    'dbname' => 0,
    'password' => ''
];
// 队列标识,不同任务类型请区分
$key = 'dux'; 
$task = new \Dux\lib\Task(string $key = '', array $config = []);
```

请在父类使用该方法，设置取值通过 $_SESSION 使用

## 获取任务列表

```php
/**
 * @param int $type 0未执行 1队列中
 * @param int $offet 偏移量
 * @param int $limit 调数
 */
$task->list($type = 0, $offet = 0, $limit = 10);
```

## 任务数量

```php
/**
 * @param int $type 0未执行 1队列中
 * @param int $startTime 开始时间范围，只针对未执行
 * @param int $stopTime 结束时间范围，只针对未执行
 */
$task->count($type, $startTime = 0, $stopTime = 0);
```

## 添加到队列

```php
/**
    * @param $time 执行时间戳
    * @param $class 执行内容,执行时通过回调使用
    * @param array $args 执行参数
 */
$task->add($time, $class, $args = [], $num = 0);
```

## 执行队列
调用此方法并加入到Cli模式定时执行，推荐按照秒,该方法根据 `pcntl` 扩展自适应多进程与单进程

```php
/**
 * @param callable $callback 执行回调函数,参数为队列数据
 * @param int $concurrent 进程数量
 * @param int $timeout 队列超时，秒
 * @param int $retry 重试次数
 */
$task->thread(callable $callback, int $concurrent = 10, int $timeout = 30, int $retry = 3);
```

