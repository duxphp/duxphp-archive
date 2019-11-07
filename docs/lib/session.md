# Session 类

将 Session 类使用缓存类进行封装保存

## 使用

```php
// $config 缓存配置
// $pre Session前缀
\Dux::Session(array $config = [], string $pre = '');
```

请在父类使用该方法，设置取值通过 $_SESSION 使用