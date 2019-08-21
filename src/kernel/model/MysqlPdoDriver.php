<?php

/**
 * mysqlpdo数据库驱动
 *
 * @author Mr.L <349865361@qq.com>
 */

namespace dux\kernel\model;

use PDO;

class MysqlPdoDriver implements DbInterface {

    protected $config = [];
    protected $link = null;
    /**
     * 连接超时 重连次数
     * @var int
     */
    protected $linkNum = 3;
    /**
     * 当前连接次数
     * @var int
     */
    protected $linkCurrentNum = 0;

    protected $sqlMeta = ['sql' => '', 'params' => [], 'link' => null];
    protected $transaction = false;

    protected $errorCode = [
        //连接失败
        'gone_away' => [
            2006,
            2013,
        ],
    ];

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function select($table, $condition = '', $params = [], $field = '*', $append = '', $return = false) {
        $field = !empty($field) ? $field : '*';
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        $table = $this->_table($table);
        return $this->query("SELECT {$field} FROM {$table} {$condition} {$append}", $params, $return);
    }

    public function aggregate($type, $table, $condition = '', $params = [], $field = '*', $append = '', $return = false) {
        $table = $this->_table($table);
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        if ($type == 'COUNT') {
            $field = '*';
        }
        $count = $this->query("SELECT {$type}({$field}) AS __total FROM {$table} {$condition} {$append}", $params, $return);
        return isset($count[0]['__total']) && $count[0]['__total'] ? $count[0]['__total'] : 0;
    }

    public function insert($table, array $columns = [], array $values = [], array $params = [], $return = false) {
        $table = $this->_table($table);
        $status = $this->execute("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $values), $params, $return);
        $id = $this->getLink()->lastInsertId();
        if ($id) {
            return $id;
        } else {
            return $status;
        }
    }

    public function update($table, $condition = '', array $columns = [], array $values = [], array $params = [], $return = false) {
        if (empty($condition)) return false;
        $table = $this->_table($table);
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        $stock = [];
        foreach ($columns as $key => $vo) {
            $stock[] = $vo . ' = ' . $values[$key];
        }
        return $this->execute("UPDATE {$table} SET " . implode(', ', $stock) . $condition, $params, $return);
    }

    public function delete($table, $condition = '', $params = [], $return = false) {
        if (empty($condition)) return false;
        $table = $this->_table($table);
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        return $this->execute("DELETE FROM {$table} {$condition}", $params, $return);
    }

    public function getFields($table) {
        $table = $this->_table($table);
        $obj = $this->getLink()->prepare("DESCRIBE {$table}");
        $obj->execute();
        return $obj->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getSql() {
        $sql = $this->sqlMeta['sql'];
        $arr = $this->sqlMeta['params'];
        uksort($arr, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
        foreach ($arr as $k => $v) {
            $sql = str_replace($k, $this->sqlMeta['link']->quote($v), $sql);
        }
        return $sql;
    }

    public function query($sql, array $params = [], $return = false) {
        return $this->exec($sql, $params, $return, \PDO::FETCH_ASSOC);
    }

    public function execute($sql, array $params = [], $return = false) {
        return $this->exec($sql, $params, $return, \PDO::FETCH_COLUMN);
    }

    private function exec($sql, $params, $return, $type) {
        $sth = $this->_bindParams($sql, $params, $this->getLink());
        if ($return) {
            return $this->getSql();
        }
        if (!IS_CLI && \dux\Config::get('dux.debug_sql')) {
            $time = microtime();
            $result = $sth->execute();
            $endTime = microtime();
            \dux\Engine::$sqls[] = [
                'sql' => $this->getSql(),
                'time' => round($endTime - $time, 2),
            ];
        }
        if ($result) {
            $this->linkCurrentNum = 0;
            $data = $sth->fetchAll($type);
            return $data;
        }
        $err = $sth->errorInfo();
        if (in_array($sth->errorCode(), $this->errorCode['gone_away']) && $this->linkCurrentNum < $this->linkNum) {
            $this->linkCurrentNum++;
            $this->link = null;
            return $this->exec($sql, $params, $return, $type);
        }
        throw new \PDOException('Database SQL: "' . $this->getSql() . '". ErrorInfo: ' . $err[2], 500);
    }

    public function beginTransaction() {
        if ($this->transaction) {
            return true;
        }
        $this->transaction = true;
        $result = $this->getLink()->beginTransaction();
        return $result;
    }

    public function commit() {
        if (!$this->transaction) {
            return false;
        }
        $this->transaction = false;
        $result = $this->getLink()->commit();
        return $result;
    }

    public function rollBack() {
        if (!$this->transaction) {
            return false;
        }
        $this->transaction = false;
        $result = $this->getLink()->rollBack();
        return $result;
    }

    protected function _bindParams($sql, array $params, $link = null) {
        $this->sqlMeta = ['sql' => $sql, 'params' => $params, 'link' => $link];
        $sth = $link->prepare($sql);
        foreach ($params as $k => $v) {
            $sth->bindValue($k, $v);
        }
        return $sth;
    }

    protected function _table($table) {
        return (false === strpos($table, ' ')) ? "`{$table}`" : $table;
    }

    protected function _connect($isMaster = true) {
        $dbArr = [];
        if (false == $isMaster && !empty($this->config['slave'])) {
            $master = $this->config;
            unset($master['slave']);
            foreach ($this->config['slave'] as $k => $v) {
                $dbArr[] = array_merge($master, $this->config['slave'][$k]);
            }
            shuffle($dbArr);
        } else {
            $dbArr[] = $this->config;
        }
        $pdo = null;
        $error = '';
        foreach ($dbArr as $db) {
            $dsn = "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}";
            try {
                $pdo = new \PDO($dsn, $db['username'], $db['password']);
                break;
            } catch (\PDOException $e) {
                $error = $e->getMessage();
            }
        }
        if (!$pdo) {
            throw new \PDOException('connect database error :' . $error, 500);
        }
        $pdo->exec("set names {$db['charset']}");
        return $pdo;
    }

    protected function getLink() {
        if (!isset($this->link)) {
            $this->link = $this->_connect(true);
        }
        return $this->link;
    }

    public function __destruct() {
        if ($this->link) {
            $this->link = null;
        }
    }

    public function checkTransSql($sql) {
        if ((strtoupper(substr($sql, 0, 6)) !== 'SELECT' && strtoupper(substr($sql, 0, 3)) !== 'SET' && strtoupper(substr($sql, 0, 5)) !== 'FLUSH')
            || strtoupper(substr($sql, -10)) === 'FOR UPDATE') {
            $this->beginTransaction();
        }
    }

    public function checkTransCommit() {
        if ($this->transaction) {
            return $this->commit();
        } else {
            return true;
        }
    }
}
