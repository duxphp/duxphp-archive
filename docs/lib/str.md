# 字符串处理

常用字符串处理方法

## 方法

### 字符串截取

- 参数：

  $str：字符串

  $length：截取长度

  $suffix：是否包含省略号

  $charset：字符串编码

```php
\dux\lib\Str::strLen($str, $length, $suffix = true, $charset = "utf-8");
```

### 编码转换

- 参数：

  $str：字符串

  $from：原始编码

  $to：目标编码

```php
\dux\lib\Str::strCharset($str, $from = 'gbk', $to = 'utf-8');
```

### 截取摘要

- 参数：

  $data：字符串

  $cut：截取长度

  $str：结尾字符

```php
\dux\lib\Str::strMake($data, $cut = 0, $str = "...");
```

### 判断字符串是否 utf8

```php
\dux\lib\Str::isUtf8($string)
```

### Html 转义

```php
\dux\lib\Str::htmlIn($str);
```

### Html 反转义

```php
\dux\lib\Str::htmlOut($str);
```

### Html 清理

```php
\dux\lib\Str::htmlClear($str);
```

### 标点符号过滤

```php
\dux\lib\Str::symbolClear($text);
```

### 随机字符串生成

```php
\dux\lib\Str::randStr($length = 5);
```

### 唯一数字编码生成

```php
\dux\lib\Str::numberUid($pre = '');
```

### 格式化数字

```php
\dux\lib\Str::intFormat($str);
```

### 价格格式化

```php
\dux\lib\Str::priceFormat($str);
```

### 价格计算

```php
\dux\lib\Str::priceCalculate($n1, $symbol, $n2, $scale = '2');
```

