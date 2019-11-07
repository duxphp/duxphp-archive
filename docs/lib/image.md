# 图片处理类

包含图片缩放、裁剪、添加水印等功能，处理类库采用 `Intervention\Image` 二次封装

## 使用

```
// $img 待处理图片路径或图片内容
// $config 图片配置

$config = [
    'type' => 'imagick', // imagick 或 gd
    'font' => '' // 字体文件路径
];
$image = new \dux\lib\Image($img = null, array $config = []);
```

## 方法

### 裁剪图片
支持连贯操作
- 参数：

  $w：裁剪宽度

  $h：裁剪高度

  $x：x 坐标

  $y：y 坐标

  $width：保存图片高度

  $height：保存图片宽度

```php
$image->crop(int $width, int $height, int $x = 0, int $y = 0);
```

### 缩放图片
支持连贯操作
- 参数：

  $width：缩放宽度

  $height：缩放高度

  $type：缩放类型，"scale" 等比例缩放、"center" 居中缩放裁剪、"fixed"、固定尺寸缩放

```php
$image->thumb(int $width, int $height, string $type = 'scale');
```

### 圆形裁剪
支持连贯操作
```php
$image->circle();
```

### 图片水印
支持连贯操作
- 参数：

  $source：水印图片绝对路径

  $locate：水印位置，1 左上角、2 上居中、3 右上角、4 左垂直居中、5 居中、6 右垂直居中、7 左下角、 8 下居中、9 右下角、0 随机

  $alpha：透明度百分比

```php
$image->water($source, int $locate = 0, int $alpha = 80);
```


### 图片合成

```php
$data = [
    [
        'type' => 'image',
        'file' => '',    //路径或图片内容
        'width' => 0,
        'height' => 0,
        'round' => false,
        'x' => 0,
        'y' => 0
    ],
    [
        'type' => 'text',
        'text' => '',
        'width' => 0,
        'height' => 0,
        'size' => '14',
        'color' => '#000000',
        'align' => 'center',
        'valign' => 'center',
        'x' => 0,
        'y' => 0
        ]
    ];

$image->generate(array $data)->get();
```

### 生成二维码
```php
$label = [
    'text' => '',
    'size' => 16,
];
$logo = [
    'file' => '',
    'width' => 100,
    'height' => 100,
];
$image->qrcode(string $text, int $size = 300, array $label = [], array $logo = [])->get();
```

### 获取图片内容
支持连贯操作
```php
$type = 'jpg';
$image->get(string $type = null, int $quality = 90);
```

### 保存图片
支持连贯操作
```php
$type = 'jpg';
$image->save(string $filename, int $quality = null, int $type = null);
```


### 输出到浏览器
支持连贯操作

```php
$image->output(string $type = null, int $quality = 90);
```


### 获取 `ImageManager` 对象

```php
$image->getObj();
```
