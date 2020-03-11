<?php

namespace dux\lib;

/**
 * 任务类
 * @author Mr.L <admin@duxphp.com>
 */
class Task {

    protected $config = [];

    protected $table = null;
    protected $locKey = '';

    /**
     * Task constructor.
     * @param string $key 队列名
     * @param array $config redis配置
     */
    public function __construct(string $key = '', array $config = []) {
        $this->locKey = $key . '_lock';
        $this->config = array_merge($this->config, $config);
        $this->table = $this->config['table'];
    }

    /**
     * 任务列表
     * @param int $type 0未执行 1队列中
     * @param int $offet
     * @param int $limit
     */
    public function list($type = 0, $offet = 0, $limit = 10) {
        if (!$type) {
            \dux\Dux::model()->table($this->table)->where(['status' => 0])->limit($offet . ',' . $limit)->select();
        } else {
            \dux\Dux::model()->table($this->table)->where(['status' => 1])->limit($offet . ',' . $limit)->select();
        }
        return $list;
    }

    /**
     * 任务数量
     * @param int $type 0未执行 1队列中
     * @param int $startTime 开始时间
     * @param int $stopTime 结束时间
     * @return int
     */
    public function count($type = 0, $startTime = 0, $stopTime = 0) {
        if (!$type) {
            return \dux\Dux::model()->table($this->table)->where(['status' => 0, 'time[>=]' => $startTime, 'time[<=]' => $stopTime])->count();
        } else {
            return \dux\Dux::model()->table($this->table)->where(['status' => 1])->count();
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
        return \dux\Dux::model()->table($this->table)->data([
            'time' => $time,
            'class' => $class,
            'args' => $args,
            'num' => 0,
            'delay' => $delay,
            'mode' => $mode
        ])->insert();
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
        $taskList = \dux\Dux::model()->table($this->table)->where(['time[<=]' => time()])->limit($concurrent)->order('time asc')->select();
        if (empty($taskList)) {
            $this->unLock();
            return 0;
        }
        $taskIds = array_column($taskList, 'queue_id');
        $taskCount = count($taskIds);
        \dux\Dux::model()->table($this->table)->where(['queue_id' => $taskIds])->data(['status' => 1])->update();

        $client = new \GuzzleHttp\Client();
        $requests = function ($taskIds) use ($url) {
            foreach ($taskIds as $id) {
                yield new \GuzzleHttp\Psr7\Request('GET', $url . '?secret=' . $this->config['secret'] . '&id=' . $id);
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests($taskIds), [
            'concurrency' => $taskCount,
            'fulfilled' => function ($response, $index) {
                $contents = $response->getBody()->getContents();
                if ($contents <> 'SUCCESS' && strpos($contents, 'ERROR:') !== false) {
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
     * @param string $secret
     * @param int $id
     * @param callable $callback
     * @param int $retry
     */
    public function execute($secret, $id, callable $callback, $retry = 3) {
        if($this->config['secret'] <> $secret) {
            return false;
        }
        if (empty($id)) {
            return false;
        }
        $task = \dux\Dux::model()->table($this->table)->where(['queue_id' => $id, 'status' => 1])->find();
        if (empty($task)) {
            return true;
        }
        $task['args'] = json_decode($task['args'], true);
        if ($callback($task) === true) {
            \dux\Dux::model()->table($this->table)->where(['queue_id' => $id])->delete();
            return true;
        }
        $data = [
            'time' => time() + $task['delay'],
            'class' => $task['class'],
            'args' => $task['args'],
            'delay' => $task['delay'],
            'mode' => $task['mode'],
            'num[+]' => 1
        ];
        if (!$task['mode']) {
            if ($task['num'] < $retry) {
                \dux\Dux::model()->table($this->table)->where(['queue_id' => $id])->data($data)->update();
            }
        } else {
            \dux\Dux::model()->table($this->table)->where(['queue_id' => $id])->data($data)->update();
        }
        return true;
    }

    /**
     * 设置锁
     * @param int $time
     */
    private function lock($time = 30) {
        return \dux\Dux::cache()->set($this->locKey, 1, $time);
    }

    /**
     * 获取锁
     * @return bool|mixed|string
     */
    private function hasLock() {
        return \dux\Dux::cache()->get($this->locKey);
    }

    /**
     * 卸载锁
     * @return int
     */
    private function unLock() {
        return \dux\Dux::cache()->del($this->locKey);
    }
}