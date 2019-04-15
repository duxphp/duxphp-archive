# 上传类

集成 阿里云、腾讯、七牛等本地上传驱动

## 配置

上传驱动配置，在实例化时传递至 "driverConfig" 参数

```php
// 驱动 => 驱动配置
[
    // 本地驱动
    'local' => [],
    // 七牛
    'qiniu' => [
        'access_key' => '',                       // 账户 key
        'secret_key' => '',                       // 账号密钥
        'bucket' => '',                           // 存储空间
        'domain' => 'http://lib.a.cuhuibao.com',  // 绑定域名
        'url' => 'up-z1.qiniup.com',              // 上传域名
     ],
     // 阿里云
     'oss' => [
         'access_id' => '',         // 账户 id
         'secret_key' => '',        // 账户密钥
         'bucket' => '',            // 存储空间
         'domain' => '',            // 绑定域名
         'url' => '',               // 上传域名
     ],
     // 腾讯cos
     'cos' => [
         'SecretId' => '',          // 账户 id
         'SecretKey' => '',         // 账户密钥
         'bucket' => '',            // 存储空间
         'domain' => '',            // 绑定域名
         'url' => '',               // 上传域名
     ],
     // 又拍云
     'upyun' => [
         'fieldname' => '',         // 自定义目录名
         'username' => '',          // 用户名
         'password' => '',          // 密码
         'bucket' => '',            // 存储空间
         'domain' => '',            // 绑定域名
         'url' => '',               // 上传域名
     ],
]
```



## 使用

```php
$config = [
    'maxSize' => 1048576,         // 上传的文件大小限制，单位字节 默认10M
    'allowExts' => [],            // 允许的文件后缀
    'rootPath' => './upload/',    // 上传根路径
    'savePath' => '',             // 保存路径
    'saveRule' => 'md5_file',     // 命名规则
    'driver' => 'local',          // 上传驱动
    'driverConfig' => [],         // 驱动配置
];
$upload = new \dux\lib\Upload($config);
```

## 方法

### 上传文件

```php
// $key 上传字段，为空自适应
$upload->upload($key = '');
```

### 远程抓取上传

```php
$upload->uploadRemote($url);
```

### 获取上传信息

上传完毕后使用

```php
$upload->getUploadFileInfo();
```

### 获取错误信息

上传失败后获取提示信息

```php
$upload->getError();
```

