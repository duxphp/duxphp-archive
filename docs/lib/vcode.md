# 图片验证码

图片验证码使用 "[slienceper](*http://silenceper.com/vcode*)" 的类库的基础上进行二次修改

## 使用

```php
/**
 * 架构函数
 *
 * @param int $width 验证码宽度 默认120
 * @param int $height 验证码高度 默认 35
 * @param int $codeNum 验证码数量 默认4
 * @param string $fontFace 中文字体路径
 */
$vcode = \dux\lib\Vcode($width = 120, $height = 35, $codeNum = 4, $fontFace = '');
```

## 方法

### 获取图片验证码

```php
// $chinese 是否中文验证码
$vcode->showImage($chinese = false);
```

返回数据

```php
[
  'image' => 'base64...',       // 验证码图片 base64
  'time' => '',                 // 验证码时间戳
  'token' => ''                 // 验证码Token
]
```

### 较验验证码

```php
// $code 用户输入验证码内容
$vcode->check($code = '', $token = '', $time = 0);
```

