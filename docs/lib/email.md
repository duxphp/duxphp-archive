# 邮件发送类

封装 phpmailer 邮件发送类，简化使用方法

## 使用

邮件发送使用连贯操作

```php
$config = [
  			'smtp_host'      => 'smtp.qq.com',  //smtp主机
  			'smtp_port'      => '465',          //端口号
        'smtp_ssl'       => false,          //安全链接
        'smtp_username'  => '',             //邮箱帐号
        'smtp_password'  => '',             //邮箱密码
        'smtp_from_to'   => '',             //发件人邮箱
        'smtp_from_name' => 'duxphp',       //发件人
];

$email = \dux\lib\Email($config);
```

## 方法

### 发送邮件

- 参数：

  $title：邮件标题

  $body：邮件内容

  $cc：抄送地址

  $bcc：密送地址

  $file：附件路径

  $to：收件人地址

```php
$email->setMail($title, $body)
			->setCc($cc)              // 多个抄送地址多次调用
			->setBcc($bcc)            // 多个密送地址多次调用
			->addAttachment($file)    // 多个附件多次调用
			->sendMail($to);          // 返回 bool
```

