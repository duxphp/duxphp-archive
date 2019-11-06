<?php

/**
 * 库驱动接口
 *
 * @author Mr.L <admin@duxphp.com>
 */

namespace dux\kernel\model;

interface ModelNoInterface {

    /**
     * 实例化驱动
     * @param array $config
     */
    public function __construct(array $config = []);

    /**
     * 获取字段
     * @param array $params
     * @return mixed
     */
    public function getFields(array $params = []);

    /**
     * 查询数据
     * @param $table
     * @param array $where
     * @param array $fields
     * @param string $order
     * @param int $limit
     * @return array
     */
    public function select(string $table, array $where, array $fields = [], string $order = '', int $limit = 0);

    /**
     * 查询数量
     * @param string $table
     * @param array $where
     * @return int
     */
    public function count(string $table, array $where);

    /**
     * 插入数据
     * @param $table
     * @param array $datas
     * @param array $params
     * @return int|bool
     */
    public function insert(string $table, array $data, array $params = []);

    /**
     * 更新数据
     * @param $table
     * @param array $where
     * @param array $data
     * @param array $params
     * @return bool
     */
    public function update(string $table, array $where = [], array $data = [], array $params = []);

    /**
     * 删除数据
     * @param $table
     * @param array $where
     * @return mixed
     */
    public function delete(string $table, array $where = []);

    /**
     * 聚合查询
     * @param $table
     * @param array $where
     * @param $group
     * @return array
     */
    public function aggregate(string $table, array $where, $group);

    /**
     * 去重查询
     * @param $table
     * @param array $where
     * @param string $key
     * @return array
     */
    public function distinct(string $table, array $where, string $key);

    /**
     * 求和数据
     * @param $table
     * @param array $where
     * @param string $field
     * @return mixed
     */
    public function sum(string $table, array $where, string $field);

    /**
     * 递增数据
     * @param string $table
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setInc(string $table, array $where = [], string $field = '', int $num = 1);

    /**
     * 递减数据
     * @param string $table
     * @param array $where
     * @param string $field
     * @param int $num
     * @return mixed
     */
    public function setDec(string $table, array $where = [], string $field = '', int $num = 1);

    /**
     * 获取连接
     * @return mixed
     */
    public function getLink();
}
