## 解压缩类

解压缩 Zip 文件

## 方法

### 压缩目录

- 参数：

  $zip_filename：保存文件路径

  $dir：压缩目录路径

  $path_replace：替换压缩包内目录

```php
\dux\lib\Zip::compress($zip_filename='xxx.zip', $dir='./', $path_replace='');
```

### 解压文件

```php
\dux\lib\Zip::decompress($zip_filename='xxx.zip', $dir='./');
```



