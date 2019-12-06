<?php

/**
 * mysqlpdo数据库驱动
 *
 * @author Mr.L <admin@duxphp.com>
 */

namespace dux\kernel\model;

use PDO;

class MysqlPdoDriver implements \dux\kernel\model\DbInterface {

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
        $data = $obj->fetchAll(\PDO::FETCH_COLUMN);
        $obj->closeCursor();
        return $data;
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
        return $this->exec($sql, $params, $return, 1);
    }

    public function execute($sql, array $params = [], $return = false) {
        return $this->exec($sql, $params, $return, 0);
    }

    private function exec($sql, $params, $return, $type) {
        $sth = $this->_bindParams($sql, $params, $this->getLink());
        $sqlStr = $this->getSql();
        if ($return) {
            return $sqlStr;
        }
        try {
            @$sth->execute();
        } catch (\PDOException $e) {
            $err = $sth->errorInfo();
            if (in_array($err[1], $this->errorCode['gone_away']) && $this->linkCurrentNum < $this->linkNum) {
                $this->linkCurrentNum++;
                $this->link = null;
                return $this->exec($sql, $params, $return, $type);
            }
            throw new \Exception('Database SQL: "' . $sqlStr . '". ErrorInfo: ' . $err[1], 500);
        }
        $this->linkCurrentNum = 0;
        if ($type) {
            $data = $sth->fetchAll(\PDO::FETCH_ASSOC);
            $sth->closeCursor();
            return $data;
        } else {
            $data = $sth->rowCount();
            $sth->closeCursor();
            return $data;
        }
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

    protected function _connect() {
        $pdo = null;
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        try {
            $pdo = new \PDO($dsn, $this->config['username'], $this->config['password'], [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->config['charset']}",
            ]);
        } catch (\PDOException $e) {
            throw new \Exception('connect database error :' . $e->getMessage(), 500);
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    protected function getLink() {
        if (!$this->link) {
            $this->link = $this->_connect();
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
