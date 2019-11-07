## 工具集

使用第三方类库封装其他非常用方法

## 方法

### 压缩目录

```php
\dux\lib\Tools::zipCompress(string $file, string $dir = '');
```

### 解压到目录

```php
\dux\lib\Tools::zipExtract(string $file, string $dir = '');
```

### 分页数据

```php
/**
 * @param int $totalItems 总页数
 * @param int $currentPage 当前页
 * @param int $perPage 每页数量
 * @param int $neighbours 分页列表量
 * @return array
 * @throws \Exception
 */
\dux\lib\Tools::page(int $totalItems, int $currentPage, int $perPage, int $neighbours = 4);
```

### 拼音转换

```php
/**
 * @param string $str 字符串
 * @param int $type 类型
 * @param bool $attr 附加类型
 * @param int $mode 模式
 * @return mixed
 */
\dux\lib\Tools::pinyin(string $str, int $type = 0, bool $attr = false, int $mode = 0);
```
### 中文分词

```php
\dux\lib\Tools::words(string $str, int $mode = 0);
```

### Sql转数组

```php
\dux\lib\Tools::sqlArray(string $sql, string $oldPre = "", string $newPre = "", string $separator = ";\n");
```
