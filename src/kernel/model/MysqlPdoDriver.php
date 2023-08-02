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
        return $this->query("SELECT {$field} FROM {$table} {$condition} {$append}", $params, $return);
    }

    public function aggregate($type, $table, $condition = '', $params = [], $field = '*', $append = '', $return = false) {
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        if ($type == 'COUNT') {
            $field = '*';
        }
        $count = $this->query("SELECT {$type}({$field}) AS __total FROM {$table} {$condition} {$append}", $params, $return);
        return isset($count[0]['__total']) && $count[0]['__total'] ? $count[0]['__total'] : 0;
    }

    public function insert($table, array $columns = [], array $values = [], array $params = [], $return = false) {
        $status = $this->execute("INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES " . implode(', ', $values), $params, $return);
        $id = $this->getLink()->lastInsertId();
        if ($id) {
            return $id;
        } else {
            return $status;
        }
    }

    public function update($table, $condition = '', array $columns = [], array $params = [], $return = false) {
        if (empty($condition)) return false;
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        return $this->execute("UPDATE {$table} SET " . implode(', ', $columns) . $condition, $params, $return);
    }

    public function delete($table, $condition = '', $params = [], $return = false) {
        if (empty($condition)) return false;
        $condition = !empty($condition) ? ' WHERE ' . $condition : '';
        return $this->execute("DELETE FROM {$table} {$condition}", $params, $return);
    }

    public function getFields($table) {
        $obj = $this->getLink()->prepare("DESCRIBE {$table}");
        $obj->execute();
        $data = $obj->fetchAll(\PDO::FETCH_COLUMN);
        $obj->closeCursor();
        return $data;
    }

    public function getSql() {
        $query = $this->sqlMeta['sql'];
        $map = $this->sqlMeta['params'];

        $query = preg_replace(
            '/"([a-zA-Z0-9_]+)"/i',
            '`$1`',
            $query
        );
        foreach ($map as $key => $value) {
            if ($value[1] === \PDO::PARAM_STR) {
                $replace = $this->sqlMeta['link']->quote($value[0]);
            } elseif ($value[1] === \PDO::PARAM_NULL) {
                $replace = 'NULL';
            } elseif ($value[1] === \PDO::PARAM_LOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = $value[0];
            }

            $query = str_replace($key, $replace, $query);
        }

        return $query;

    }

    public function query($sql, array $params = [], $return = false) {
        return $this->exec($sql, $params, $return, 1);
    }

    public function execute($sql, array $params = [], $return = false) {
        return $this->exec($sql, $params, $return, 0);
    }

    private function exec($sql, $params, $return, $type) {
        $sth = $this->_bindParams($sql, $params, $this->getLink());
        if ($return) {
            $sqlStr = $this->getSql();
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
            $sqlStr = $sqlStr ?: $this->getSql();
            throw new \Exception('Database SQL: "' . $sqlStr . '". ErrorInfo: ' . $err[1] . ' ' . $err[2], 500);
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
            $sth->bindValue($k, $v[0], $v[1]);
        }
        return $sth;
    }

    protected function _connect() {
        $pdo = null;


        $params = $this->config;

        $dsn = 'mysql:';

        if ($params['socket']) {
            $dsn .= 'unix_socket=' . $params['socket'] . ';';
        }
        if ($params['host']) {
            $dsn .= 'host=' . $params['host'] . ';';
        }

        if ($params['port']) {
            $dsn .= 'port=' . $params['port'] . ';';
        }

        if ($params['dbname']) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }

        if ($params['charset']) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }


        try {
            $pdo = new \PDO($dsn, $this->config['username'], $this->config['password']);
            $pdo->exec("SET NAMES {$this->config['charset']}");
            $pdo->exec('SET SQL_MODE=' . ($this->config['strict'] ? 'NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES' : '""'));
        } catch (\PDOException $e) {
            throw new \Exception('connect database error :' . $e->getMessage(), 500);
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public function getLink() {
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
