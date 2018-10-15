<?php

/**
 * mysqlpdo数据库驱动
 *
 * @author Mr.L <349865361@qq.com>
 */

namespace dux\kernel\model;

use dux\vendor\Profiler;

class MysqlPdoDriver implements DbInterface {

    protected $config = [];
    protected $link = NULL;
    protected $sqlMeta = array('sql' => '', 'params' => [], 'link' => NULL);
    protected $transaction = false;

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function select($table, $condition = '', $params = [], $field = '*', $lock = false, $order = NULL, $limit = NULL, $group = NULL) {
        $field = !empty($field) ? $field : '*';
        $order = !empty($order) ? ' ORDER BY ' . $order : '';
        $limit = !empty($limit) ? ' LIMIT ' . $limit : '';
        $group = !empty($group) ? ' GROUP BY ' . $group : '';
        $lock = $lock ? 'for update' : '';
        $table = $this->_table($table);
        return $this->query("SELECT {$field} FROM {$table} {$condition} {$group} {$order} {$limit} {$lock}", $params);
    }

    public function count($table, $condition = '', $params = [], $group = NULL) {
        $table = $this->_table($table);
        $group = !empty($group) ? ' GROUP BY ' . $group : '';
        $count = $this->query("SELECT COUNT(*) AS __total FROM {$table} {$condition} {$group}" , $params);
        return isset($count[0]['__total']) && $count[0]['__total'] ? $count[0]['__total'] : 0;
    }

    public function query($sql, array $params = []) {
        $sth = $this->_bindParams($sql, $params, $this->getLink());
        $sTime = -Profiler::elasped();
        $result = $sth->execute();
        Profiler::saveQuery($this->getSql(), $sTime, 'db');
        if ($result) {
            $data = $sth->fetchAll(\PDO::FETCH_ASSOC);
            return $data;
        }
        $err = $sth->errorInfo();
        throw new \Exception('Database SQL: "' . $this->getSql() . '". ErrorInfo: ' . $err[2], 500);
    }

    public function execute($sql, array $params = []) {
        $sth = $this->_bindParams($sql, $params, $this->getLink());
        $sTime = -Profiler::elasped();
        Profiler::saveQuery($this->getSql(), $sTime, 'db');
        $result = $sth->execute();
        if ($result) {
            $affectedRows = $sth->rowCount();
            return $affectedRows;
        }
        $err = $sth->errorInfo();
        throw new \Exception('Database SQL: "' . $this->getSql() . '". ErrorInfo: ' . $err[2], 500);
    }

    public function insert($table, array $data = [], array $params = []) {
        $table = $this->_table($table);
        $values = [];
        $keys = [];
        $data = $data['data'];
        foreach ($data as $k => $v) {
            $keys[] = "`{$k}`";
            $values[":{$k}"] = $v;
        }
        $status = $this->execute("INSERT INTO {$table} (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ")", $params);
        $id = $this->getLink()->lastInsertId();
        if ($id) {
            return $id;
        } else {
            return $status;
        }
    }

    public function update($table, $condition = '', $whereParams = [], array $data = [], array $dataParams = []) {
        if (empty($condition)) return false;
        $table = $this->_table($table);
        $sql = $data['sql'];
        return $this->execute("UPDATE {$table} SET " . implode(', ', $sql) . $condition, $whereParams + $dataParams);

    }

    public function sum($table, $condition = '', $params = [], $field) {
        $table = $this->_table($table);
        
        $sum = $this->query("SELECT SUM(`{$field}`) as __sum FROM {$table} {$condition} ", $params);
        return isset($sum[0]['__sum']) && $sum[0]['__sum'] ? $sum[0]['__sum'] : 0;
    }

    public function increment($table, $condition = '', $params = [], $field, $num = 1) {
        if (empty($condition) || empty($field)) return false;
        $table = $this->_table($table);
        
        return $this->execute("UPDATE {$table} SET {$field} = {$field} + {$num} " . $condition, $params);
    }

    public function decrease($table, $condition = '',$params = [], $field, $num = 1) {
        if (empty($condition) || empty($field)) return false;
        $table = $this->_table($table);
        
        return $this->execute("UPDATE {$table} SET {$field} = {$field} - {$num} " . $condition, $params);
    }

    public function delete($table, $condition = '', $params = []) {
        if (empty($condition)) return false;
        $table = $this->_table($table);
        
        return $this->execute("DELETE FROM {$table} {$condition}", $params);
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

    public function beginTransaction() {
        if($this->transaction) {
            return true;
        }
        $this->transaction = true;
        $sTime = -Profiler::elasped();
        $result = $this->getLink()->beginTransaction();
        Profiler::saveQuery("begin", $sTime, 'db');
        return $result;
    }

    public function commit() {
        if(!$this->transaction) {
            return false;
        }
        $this->transaction = false;
        $sTime = -Profiler::elasped();
        $result = $this->getLink()->commit();
        Profiler::saveQuery("commit", $sTime, 'db');
        return $result;
    }

    public function rollBack() {
        if(!$this->transaction) {
            return false;
        }
        $this->transaction = false;
        $sTime = -Profiler::elasped();
        $result = $this->getLink()->rollBack();
        Profiler::saveQuery("rollback", $sTime, 'db');
        return $result;
    }

    protected function _bindParams($sql, array $params, $link = null) {
        $this->sqlMeta = array('sql' => $sql, 'params' => $params, 'link' => $link);
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
            throw new \Exception('connect database error :' . $error, 500);
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
            $this->link = NULL;
        }
    }

    public function checkTransSql($sql){
        if ((strtoupper(substr($sql, 0, 6)) !== 'SELECT' && strtoupper(substr($sql, 0, 3)) !== 'SET' && strtoupper(substr($sql, 0, 5)) !== 'FLUSH')
            || strtoupper(substr($sql, -10)) === 'FOR UPDATE') {
            $this->beginTransaction();
        }
    }

    public function checkTransCommit(){
        if($this->transaction){
            return $this->commit();
        }else{
            return true;
        }
    }
}