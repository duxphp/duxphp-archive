# 汉字转拼音

将汉字转为拼音，来源于 canphp

## 使用

```php
$pinyin = new \dux\lib\Pinyin();
```

## 方法

汉字转拼音

- 参数：

  $str：所要转化拼音的汉字

  $utf8：汉字编码是否为utf8

```php
$pinyin->output($str, $utf8 = true);
```

