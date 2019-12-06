<?php

/**
 * 无限分类
 */

namespace dux\lib;

class Category {

    /**
     * 原始数据
     * @var array
     */
    private $rawList = [];

    /**
     * 格式化数据
     * @var array
     */
    private $formatList = [];

    /**
     * 分类样式
     * @var array
     */
    private $icon = ['│', '├', '└'];

    /**
     * 映射字段
     * @var array
     */
    private $field = [];

    /**
     * Category constructor.
     * @param array $field
     */
    public function __construct(array $field = []) {
        $this->field['id'] = isset($field['0']) ? $field['0'] : 'id';
        $this->field['pid'] = isset($field['1']) ? $field['1'] : 'pid';
        $this->field['title'] = isset($field['2']) ? $field['2'] : 'title';
        $this->field['fulltitle'] = isset($field['3']) ? $field['3'] : 'fulltitle';
    }

    /**
     * 获取同级分类
     * @param int $pid
     * @param array $data
     * @return array
     */
    public function getChild(int $pid, array $data = []) {
        $childs = [];
        if (empty($data)) {
            $data = $this->rawList;
        }
        foreach ($data as $Category) {
            if ($Category[$this->field['pid']] == $pid)
                $childs[] = $Category;
        }
        return $childs;
    }

    /**
     * 获取树形分类
     * @param array $data
     * @param int $id
     * @return array
     */
    public function getTree(array $data, int $id = 0) {
        //数据为空，则返回
        if (empty($data))
            return [];

        $this->rawList = [];
        $this->formatList = [];
        $this->rawList = $data;
        $this->_searchList($id);
        return $this->formatList;
    }

    /**
     * 获取分类路径
     * @param array $data
     * @param int $id
     * @return array
     */
    public function getPath(array $data, int $id) {

        $this->rawList = $data;
        while (1) {
            $id = $this->_getPid($id);
            if ($id == 0) {
                break;
            }
        }
        return array_reverse($this->formatList);
    }

    /**
     * 递归分类
     * @param int $id
     * @param string $space
     */
    private function _searchList(int $id = 0, string $space = "") {
        //下级分类的数组
        $childs = $this->getChild($id);
        //如果没下级分类，结束递归
        if (!($n = count($childs)))
            return;
        $cnt = 1;
        //循环所有的下级分类
        for ($i = 0; $i < $n; $i++) {
            $pre = "";
            $pad = "";
            if ($n == $cnt) {
                $pre = $this->icon[2];
            } else {
                $pre = $this->icon[1];
                $pad = $space ? $this->icon[0] : "";
            }
            $childs[$i][$this->field['fulltitle']] = ($space ? $space . $pre : "") . $childs[$i][$this->field['title']];
            $this->formatList[] = $childs[$i];
            //递归下一级分类
            $this->_searchList($childs[$i][$this->field['id']], $space . $pad . "&nbsp;&nbsp;");
            $cnt++;
        }
    }

    /**
     * 获取PID
     * @param int $id
     * @return int
     */
    private function _getPid(int $id) {
        foreach ($this->rawList as $key => $value) {
            if ($this->rawList[$key][$this->field['id']] == $id) {
                $this->formatList[] = $this->rawList[$key];
                return $this->rawList[$key][$this->field['pid']];
            }
        }
        return 0;
    }

}