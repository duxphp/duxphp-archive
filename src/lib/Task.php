<?php

namespace dux\lib;

/**
 * 任务类
 * @author Mr.L <admin@duxphp.com>
 */
class Task {

    protected $config = [
        'host' => 'localhost',
        'port' => 6379,
        'dbname' => 0,
        'password' => ''
    ];
    protected $object = null;
    protected $key = '';
    protected $tasKey = '';
    protected $locKey = '';

    /**
     * Task constructor.
     * @param string $key 队列名
     * @param array $config redis配置
     */
    public function __construct(string $key = '', array $config = []) {
        $this->key = $key;
        $this->tasKey = $key . '_task';
        $this->locKey = $key . '_lock';
        $this->config = array_merge($this->config, $config);
        //$this->object = new \dux\lib\Redis($this->config);
    }

    /**
     * 任务列表
     * @param int $type 0未执行 1队列中
     * @param int $offet
     * @param int $limit
     */
    public function list($type = 0, $offet = 0, $limit = 10) {
        if (!$type) {
            $list = (array)$this->obj()->zRangeByScore($this->key, 0, time(), ['limit' => [$offet, $limit]]);
        } else {
            $list = (array)$this->obj()->lRange($this->tasKey, $offet, $offet + $limit - 1);
        }
        return $list;
    }

    /**
     * 任务数量
     * @param int $type 0未执行 1队列中
     * @param int $startTime 未执行开始时间
     * @param int $stopTime 未执行结束时间
     * @return int
     */
    public function count($type = 0, $startTime = 0, $stopTime = 0) {
        if (!$type) {
            return intval($this->obj()->zCount($this->key, $startTime, $stopTime));
        } else {
            return intval($this->obj()->lLen($this->tasKey));
        }
    }

    /**
     * 添加队列
     * @param $time
     * @param $class
     * @param array $args
     * @param int $delay
     * @param int $mode
     * @return int
     */
    public function add($time, $class, $args = [], $delay = 5, $mode = 0) {
        return $this->obj()->zAdd(
            $this->key,
            $time,
            json_encode([
                'time' => $time,
                'class' => $class,
                'args' => $args,
                'num' => 0,
                'delay' => $delay,
                'mode' => $mode
            ], JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 执行队列
     * @param string $url 队列Url
     * @param int $concurrent 进程数量
     * @param int $timeout 队列超时，秒
     * @return int
     */
    public function thread($url, int $concurrent = 10, int $timeout = 30) {
        if ($this->hasLock()) {
            return -1;
        }
        $this->lock($timeout);
        $taskList = $this->obj()->zRangeByScore($this->key, 0, time(), ['limit' => [0, $concurrent]]);

        $concurrent = intval($this->obj()->lLen($this->tasKey));
        foreach ($taskList as $data) {
            if ($this->obj()->zRem($this->key, $data)) {
                if ($this->obj()->rPush($this->tasKey, $data)) {
                    $concurrent++;
                }
            }
        }
        if (!$concurrent) {
            $this->unLock();
            return 0;
        }

        $client = new \GuzzleHttp\Client();
        $requests = function ($total) use($url) {
            for ($i = 0; $i < $total; $i++) {
                yield new \GuzzleHttp\Psr7\Request('GET', $url);
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests($concurrent), [
            'concurrency' => $concurrent,
            'fulfilled' => function ($response, $index) {
                $contents = $response->getBody()->getContents();
                if($contents <> 'SUCCESS' && strpos($contents, 'ERROR:') !== false) {
                    dux_log('Task:' . $contents);
                }
            },
            'rejected' => function ($reason, $index) {
                dux_log('Task:Request failed');
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        $this->unLock();
        return 1;
    }

    /**
     * 任务执行
     * @param callable $callback
     * @param int $retry
     */
    public function execute(callable $callback, $retry = 3) {
        $task = $this->obj()->lPop($this->tasKey);
        if (empty($task)) {
            return true;
        }
        $task = json_decode($task, true);
        if ($callback($task) === true) {
            return true;
        }
        if (!$task['mode']) {
            if ($task['num'] < $retry) {
                $this->obj()->rPush($this->tasKey, json_encode([
                    'time' => time() + $task['delay'],
                    'class' => $task['class'],
                    'args' => $task['args'],
                    'num' => $task['num'] + 1,
                    'delay' => $task['delay'],
                    'mode' => $task['mode']
                ], JSON_UNESCAPED_UNICODE));
            }
        } else {
            $this->obj()->rPush($this->tasKey, json_encode([
                'time' => time() + $task['delay'],
                'class' => $task['class'],
                'args' => $task['args'],
                'num' => 0,
                'delay' => $task['delay'],
                'mode' => $task['mode']
            ], JSON_UNESCAPED_UNICODE));
        }
        return true;
    }

    /**
     * 设置锁
     * @param int $time
     */
    private function lock($time = 30) {
        $this->obj()->set($this->locKey, 1, $time);
    }

    /**
     * 获取锁
     * @return bool|mixed|string
     */
    private function hasLock() {
        return $this->obj()->get($this->locKey);
    }

    /**
     * 卸载锁
     * @return int
     */
    private function unLock() {
        return $this->obj()->del($this->locKey);
    }

    /**
     * 数据对象
     * @return \Redis|null
     */
    private function obj() {
        if($this->object) {
            return $this->object;
        }
        $this->object = new \Redis();
        $this->object->connect($this->config['host'], $this->config['port']);
        if ($this->config['password']) {
            $this->object->auth($this->config['password']);
        }
        $this->object->select($this->config['dbname']);
        return $this->object;
    }

    /**
     * 断开连接
     */
    private function close() {
        $this->object->close();
    }
}