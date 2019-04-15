# Session 类

将 Session 类使用缓存类进行封装保存

## 使用

```php
// $driver 为缓存配置名
\Dux::Session($driver = 'default');
```

请在父类使用该方法，设置取值通过 $_SESSION 使用