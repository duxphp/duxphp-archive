<?php

/**
 * 数据库驱动接口
 *
 * @author Mr.L <349865361@qq.com>
 */

namespace dux\kernel\model;

interface DbInterface {
	
	/**
	 * 构建函数
	 * @param array $config 数据库配置
	 */
	public function __construct($config);

    /**
     * 执行SQL查询
     * @param $sql
     * @param array $params
     * @return mixed
     */
	public function query($sql, array $params);

    /**
     * 执行SQL读写
     * @param $sql
     * @param array $params
     * @return mixed
     */
	public function execute($sql, array $params);

    /**
     * 数据查询
     * @param $table
     * @param $condition
     * @param array $params
     * @param $field
     * @param $order
     * @param $limit
     * @return mixed
     */
	public function select($table, $condition, $params = [], $field, $order, $limit);

    /**
     * 插入数据
     * @param $table
     * @param array $data
     * @param array $params
     * @return mixed
     */
	public function insert($table, array $data, array $params = []);

    /**
     * 更新数据
     * @param $table
     * @param $condition
     * @param array $whereParams
     * @param array $data
     * @param array $dataParams
     * @return mixed
     */
	public function update($table, $condition, $whereParams = [], array $data, array $dataParams = []);

    /**
     * 删除数据
     * @param $table
     * @param $condition
     * @param array $params
     * @return mixed
     */
	public function delete($table, $condition, $params = []);

    /**
     * 查询统计
     * @param $table
     * @param $condition
     * @param array $params
     * @return mixed
     */
	public function count($table, $condition, $params = []);


    /**
     * 递增字段
     * @param $table
     * @param $condition
     * @param array $params
     * @param $field
     * @param $num
     * @return mixed
     */
	public function increment($table, $condition, $params = [], $field, $num);

    /**
     * 递减字段
     * @param $table
     * @param $condition
     * @param array $params
     * @param $field
     * @param $num
     * @return mixed
     */
	public function decrease($table, $condition, $params = [], $field, $num);

    /**
     * 获取表字段
     * @param $table
     * @return mixed
     */
	public function getFields($table);
	
	/**
	 * 获取最后执行sql
	 * @return string
	 */
	public function getSql();
	
	/**
	 * 事务开始
	 * @return boolean
	 */
	public function beginTransaction();
	
	/**
	 * 事务提交
	 * @return boolean
	 */
	public function commit();
	
	/**
	 * 事务回滚
	 * @return boolean
	 */
	public function rollBack();
}