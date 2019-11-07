# 图片验证码

图片验证码使用 "Gregwar\Captcha" 二次封装

## 使用

```php
// $config 缓存配置
$vcode = \dux\lib\Vcode(array $config = []);
```

## 方法

### 获取图片验证码

```php
/**
 * 获取验证码
 * @param int $width 宽度
 * @param int $height 高度
 * @param int $expire 过期时间
 * @param string $key 密钥
 * @param int $quality 图片质量
 * @return array
 * @throws \Exception
 */
$vcode->get(int $width = 100, int $height = 50, int $expire = 120, string $key = '', int $quality = 90);
```

返回数据

```php
[
  'image' => 'base64...',       // 验证码图片 base64
  'token' => ''                 // 验证码Token
]
```

### 较验验证码

```php
// $code 用户输入验证码内容
// $token 验证码token
// $key 密钥
$vcode->has(string $code, string $token, string $key = '');
```

