<?php

namespace dux\lib;

/**
 * 工具集
 */
class Tools {

    /**
     * 压缩目录
     * @param string $file
     * @param string $dir
     * @throws \Exception
     */
    static public function zipCompress(string $file, string $dir = '') {
        $zippy = \Alchemy\Zippy\Zippy::load();
        $zippy->create($file, [
            'folder' => $dir
        ], true);
    }

    /**
     * 解压到目录
     * @param string $file
     * @param string $dir
     * @throws \Exception
     */
    static public function zipExtract(string $file, string $dir = '') {
        $zippy = \Alchemy\Zippy\Zippy::load();
        $zippy->open($file)->extract($dir);
    }

    /**
     * 分页数据
     * @param int $totalItems 总页数
     * @param int $currentPage 当前页
     * @param int $perPage 每页数量
     * @param int $neighbours 分页列表量
     * @return array
     * @throws \Exception
     */
    static public function page(int $totalItems, int $currentPage, int $perPage, int $neighbours = 4) {
        if ($perPage <= 0) {
            throw new \Exception('每页数量不能小于1', 500);
        }
        if ($neighbours <= 0) {
            throw new \Exception('分页显示数量不能小于1', 500);
        }
        $output = [];
        $totalPage = ceil($totalItems / $perPage);
        if (!$totalPage) {
            $totalPage = 1;
        }
        $current = $currentPage ? $currentPage : 1;
        if ($current > $totalPage) {
            $current = $totalPage;
        }
        $tagOffset = ($current - 1) * $perPage;
        $tagOffset = ($tagOffset >= 0) ? $tagOffset : 0;
        $output['current'] = $current;
        $output['page'] = $totalPage;
        $output['count'] = $totalItems;
        $output['first'] = 1;
        $output['last'] = $totalPage;
        $output['prev'] = (($current <= 1) ? 1 : ($current - 1));
        $output['next'] = (($current == $totalPage) ? $totalPage : ($current + 1));
        $output['offset'] = $tagOffset;
        if ($totalPage <= $neighbours) {
            $output['pageList'] = range(1, $totalPage);
        } elseif ($current <= $neighbours / 2) {
            $output['pageList'] = range(1, $neighbours);
        } elseif ($current <= $totalPage - $neighbours / 2) {
            $right = $current + (int)($neighbours / 2);
            $output['pageList'] = range($right - $neighbours + 1, $right);
        } else {
            $output['pageList'] = range($totalPage - $neighbours + 1, $totalPage);
        }
        return $output;
    }

    /**
     * 拼音转换
     * @param string $str 字符串
     * @param int $type 类型
     * @param bool $attr 附加类型
     * @param int $mode 模式
     * @return mixed
     */
    static public function pinyin(string $str, int $type = 0, bool $attr = false, int $mode = 0) {
        if ($mode == 1) {
            $class = 'Overtrue\Pinyin\MemoryFileDictLoader';
        }
        if ($mode == 2) {
            $class = 'Overtrue\Pinyin\GeneratorFileDictLoader';
        }
        $pinyin = new Overtrue\Pinyin\Pinyin();
        if (!$type) {
            if (!$attr) {
                //字符串
                return $pinyin->sentence($str);
            } else {
                //字符串带注音
                return $pinyin->sentence($str, PINYIN_TONE);
            }
        }
        if ($type == 1) {
            if (!$attr) {
                //数组
                return $pinyin->convert($str);
            } else {
                //数组带注音
                return $pinyin->convert($str, PINYIN_TONE);
            }
        }
        if ($type == 2) {
            //链接
            return $pinyin->permalink($str);
        }
        if ($type == 3) {
            if (!$attr) {
                //首字母字符串
                return $pinyin->abbr($str);
            } else {
                //首字母字符串加数字
                return $pinyin->abbr($str, PINYIN_KEEP_NUMBER);
            }
        }
        if ($type == 4) {
            if (!$attr) {
                //姓名
                return $pinyin->name($str);
            } else {
                //姓名带注音
                return $pinyin->name($str, PINYIN_TONE);
            }
        }
    }

    /**
     * 中文分词
     * @param string $str
     * @param int $mode
     * @return array
     */
    static public function words(string $str, int $mode = 0) {
        \Fukuball\Jieba\Jieba::init();
        \Fukuball\Jieba\Finalseg::init();
        if ($mode == 1) {
            //全模式
            return \Fukuball\Jieba\Jieba::cut($str, true);
        } else if ($mode == 2) {
            //搜索引擎模式
            return \Fukuball\Jieba\Jieba::cutForSearch($str);
        } else {
            //精确模式
            return \Fukuball\Jieba\Jieba::cut($str);
        }
    }

    /**
     * Sql转数组
     * @param $sql
     * @param string $oldPre
     * @param string $newPre
     * @param string $separator
     * @return array|bool
     */
    static public function sqlArray(string $sql, string $oldPre = "", string $newPre = "", string $separator = ";\n") {
        $commenter = ['#', '--'];
        if (!empty($sql)) {
            return false;
        }
        $content = str_replace([$oldPre, "\r"], [$newPre, "\n"], $sql);
        $segment = explode($separator, trim($content));
        $data = [];
        foreach ($segment as $statement) {
            $sentence = explode("\n", $statement);
            $newStatement = [];
            foreach ($sentence as $subSentence) {
                if ('' != trim($subSentence)) {
                    $isComment = false;
                    foreach ($commenter as $comer) {
                        if (preg_match("/^(" . $comer . ")/is", trim($subSentence))) {
                            $isComment = true;
                            break;
                        }
                    }
                    if (!$isComment) {
                        $newStatement[] = $subSentence;
                    }
                }
            }
            $data[] = $newStatement;
        }
        foreach ($data as $statement) {
            $newStmt = '';
            foreach ($statement as $sentence) {
                $newStmt = $newStmt . trim($sentence) . "\n";
            }
            if (!empty($newStmt)) {
                $result[] = $newStmt;
            }
        }
        return $result;
    }
}