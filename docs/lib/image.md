# 图片处理类

包含图片缩放、裁剪、添加水印等功能

## 使用

```
// $img 待处理图片绝对路径
// $driver 图片处理驱动 gd 或 imagick
$image = new \dux\lib\Image($img, $driver = 'gd');
```

## 方法

### 裁剪图片

- 参数：

  $w：裁剪宽度

  $h：裁剪高度

  $x：x 坐标

  $y：y 坐标

  $width：保存图片高度

  $height：保存图片宽度

```php
$image->crop($w, $h, $x = 0, $y = 0, $width = null, $height = null);
```

### 缩放图片

- 参数：

  $width：缩放宽度

  $height：缩放高度

  $type：缩放类型，"scale" 等比例缩放、"center" 居中缩放裁剪、"fixed"、固定尺寸缩放

```php
$image->thumb($width, $height, $type = 'scale');
```

### 图片水印

- 参数：

  $source：水印图片绝对路径

  $locate：水印位置，1 左上角、2 上居中、3 右上角、4 左垂直居中、5 居中、6 右垂直居中、7 左下角、 8 下居中、9 右下角、0 随机

  $alpha：透明度百分比

```php
$image->water($source, $locate = 0, $alpha = 80);
```

### 输出图片

图片处理完毕进行输出保存

- 参数：

  $filename：文件保存绝对路径，如 "\upload\img.jpg"

  $type：图片保存类型，默认为原始类型

```php
$image->thumb($width, $height, $type = 'scale')->output($filename, $type = null);
```

