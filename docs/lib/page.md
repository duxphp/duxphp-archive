# 分页类

常用数据分页类，返回分页参数信息

## 使用

```
/**
 * 初始化分页类
 *
 * @param 数据总数
 * @param 当前页码
 * @param $perPage 每页数量
 * @param int $neighbours 分页可视数量
 */
$page = new \dux\lib\Pagination(totalItems, $currentPage, $perPage, $neighbours = 4);
```

## 方法

获取分页数据

```php
$page->build();
```

返回：

```php
[
  'current' => 1,                   // 当前页码
  'first' => 1,                     // 第一页页码
  'last' => 5,                      // 尾页页码
  'prev' => 1,                      // 上一页页码
  'next' => 2,                      // 下一页页码
  'offset' => 0,                    // 数据起始偏移位置，如 每页数量为10，第二页起始行则为 11 
  'count' => 50,                    // 数据总数
  'page' => 5,                      // 分页总数
  'pageList' => [1, 2, 3, 4]        // 可视分页数组
]
```

