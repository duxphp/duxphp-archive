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
    }

    /**
     * 任务列表
     * @param int $type 0未执行 1队列中
     * @param int $offet
     * @param int $limit
     */
    public function list($type = 0, $offet = 0, $limit = 10) {
        if (!type) {
            $taskList = $this->obj()->zRangeByScore($this->key, 0, time(), ['limit' => [$offet, $offet + $limit - 1]]);
        } else {
            $taskList = $this->obj()->lRange($this->tasKey, $offet, $offet + $limit - 1);
        }
        return $taskList;
    }

    /**
     * 任务数量
     * @param $type 0未执行 1队列中
     * @param int $startTime 未执行开始时间
     * @param int $stopTime 未执行结束时间
     * @return int
     */
    public function count($type, $startTime = 0, $stopTime = 0) {
        if (!type) {
            return intval($this->obj()->zCount($this->tasKey, $startTime, $stopTime));
        } else {
            return intval($this->obj()->lLen($this->key));
        }
    }

    /**
     * 添加队列
     * @param $time
     * @param $class
     * @param array $args
     * @param int $num
     * @return int
     */
    public function add($time, $class, $args = [], $num = 0) {
        return $this->obj()->zAdd(
            $this->key,
            $time,
            json_encode([
                'time' => $time,
                'class' => $class,
                'args' => $args,
                'num' => $num
            ], JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * 执行队列
     * @param callable $callback 执行函数
     * @param int $concurrent 进程数量
     * @param int $timeout 队列超时，秒
     * @param int $retry 重试次数
     * @return int
     */
    public function thread(callable $callback, int $concurrent = 10, int $timeout = 30, int $retry = 3) {
        if(!function_exists('pcntl_fork')) {
            return  $this->single($callback, $timeout, $retry);
        }
        if ($this->hasLock()) {
            return -1;
        }
        $this->lock($timeout);
        $taskList = $this->obj()->zRangeByScore($this->key, 0, time(), ['limit' => [0, $concurrent]]);
        $concurrent = intval($this->obj()->lLen($this->key));
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
        $pidData = [];
        for ($i = 0; $i <= $concurrent; $i++) {
            $pid = \pcntl_fork();
            if ($pid == -1) {
                die("Fork failed");
            }
            if ($pid > 0) {
                $pidData[] = $pid;
            }
            if ($pid == 0) {
                $this->execute($callback, $retry);
                exit;
            }
        }
        while (count($pidData) > 0) {
            pcntl_wait($status);
            foreach ($pidData as $key => $pid) {
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                if ($res == -1 || $res > 0) {
                    unset($pidData[$key]);
                }
            }
        }
        $this->unLock();
        return 1;
    }

    public function single(callable $callback, int $timeout = 30, int $retry = 3) {
        if ($this->hasLock()) {
            return -1;
        }
        $this->lock($timeout);
        $taskList = $this->obj()->zRangeByScore($this->key, 0, time(), ['limit' => [0, 1]]);
        $data = $taskList[0];
        if (!$data) {
            $this->unLock();
            return 0;
        }
        if ($this->obj()->zRem($this->key, $data)) {
            if (!$this->obj()->rPush($this->tasKey, $data)) {
                $this->unLock();
                return 0;
            }
        } else {
            $this->unLock();
            return 0;
        }
        $this->execute($callback, $retry);
        $this->unLock();
        return 1;
    }

    /**
     * 任务执行
     * @param callable $callback
     * @param int $retry
     */
    private function execute(callable $callback, $retry = 3) {
        $task = $this->obj()->lPop($this->tasKey);
        if ($task) {
            try {
                if (!$callback(json_decode($task, true))) {
                    if ($task['num'] < $retry) {
                        $this->obj()->rPush($this->tasKey, json_encode([
                            'time' => $task['time'],
                            'class' => $task['class'],
                            'args' => $task['arge'],
                            'num' => $task['num'] + 1
                        ], JSON_UNESCAPED_UNICODE));
                    }
                }
            } catch (\Exception $e) {
                \dux\Dux::log('Task: ' . $e->getMessage() . " line {$this->line} in file {$this->file}");
            }
        }
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
        if ($this->object) {
            return $this->object;
        }
        $this->object = new \Redis();
        $this->object->connect($this->config['host'], $this->config['port']);
        if ($config['password']) {
            $this->object->auth($config['password']);
        }
        $this->object->select($this->config['dbname']);
        return $this->object;
    }
}