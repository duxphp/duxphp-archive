<?php
namespace dux\lib;

/**
 * 分页类
 *
 * @author  Mr.L <349865361@qq.com>
 */

class Pagination {

    private $basePage = 1;
    private $totalCount = 'count';
    private $totalPage = 'page';
    private $tagFirst = 'first';
    private $tagPrev = 'prev';
    private $tagPageList = 'pageList';
    private $tagCurrent = 'current';
    private $tagNext = 'next';
    private $tagLast = 'last';
    private $tagOffset = 'offset';
    private $totalItems;
    private $currentPage;
    private $perPage;
    private $neighbours;

    /**
     * 初始化分页类
     * Pagination constructor.
     * @param $totalItems
     * @param $currentPage
     * @param $perPage
     * @param int $neighbours
     * @throws \Exception
     */
    public function __construct($totalItems, $currentPage, $perPage, $neighbours = 4) {
        $this->totalItems = (int)$totalItems;
        $this->currentPage = (int)$currentPage;
        $this->perPage = (int)$perPage;
        $this->neighbours = (int)$neighbours;
        if ($this->perPage <= 0) {
            throw new \Exception('每页数量不能小于1', 500);
        }
        if ($this->neighbours <= 0) {
            throw new \Exception('分页显示数量不能小于1', 500);
        }
    }

    /**
     * build
     * @return array
     */
    public function build() {
        $output = array();
        $current = max(intval($this->currentPage), 1);
        $totalPage = ceil( $this->totalItems / $this->perPage );
        if(!$totalPage) {
            $totalPage = 1;
        }
        $tagOffset = ($current - 1) * $this->perPage;
        $tagOffset = ($tagOffset >= 0) ? $tagOffset : 0;

        $output[$this->tagCurrent] = $current;
        $output[$this->totalPage] = $totalPage;
        $output[$this->totalCount] = $this->totalItems;
        $output[$this->tagFirst] = $this->basePage;
        $output[$this->tagLast] = $totalPage;

        $output[$this->tagPrev] = ( ( $current <= 1  ) ? 1 : ($current - 1) );
        $output[$this->tagNext] = ( ( $current == $totalPage ) ? $totalPage : ($current + 1));

        $output[$this->tagOffset] = $tagOffset;


        if($totalPage <= $this->neighbours ){
            $output[$this->tagPageList] = range(1, $totalPage);
        }elseif( $current <= $this->neighbours/2) {
            $output[$this->tagPageList] = range(1, $this->neighbours);
        }elseif( $current <= $totalPage - $this->neighbours/2 ){
            $right = $current + (int)($this->neighbours/2);
            $output[$this->tagPageList] = range($right-$this->neighbours+1, $right);
        }else{
            $output[$this->tagPageList] = range($totalPage-$this->neighbours+1, $totalPage);
        }

        return $output;
    }
}