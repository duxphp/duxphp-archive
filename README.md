<p align="center">
  <a href="https://github.com/duxphp/duxphp">
   <img alt="DuxCMS" src="https://github.com/duxphp/duxphp/blob/master/docs/logo.png?raw=true">
  </a>
</p>

<p align="center">
  为快速开发而生
</p>

<p align="center">
  <a href="https://github.com/duxphp/duxphp">
    <img alt="maven" src="https://img.shields.io/badge/duxphp-v2-blue.svg">
  </a>

  <a href="https://github.com/duxphp/duxphp/blob/master/LICENSE">
    <img alt="code style" src="https://img.shields.io/badge/apache-licenses-brightgreen.svg">
  </a>
</p>

# 简介

DuxPHP是一款轻量级高效率的PHP开发框架，框架支持HMVC模式，同时兼容Http Api模式，框架经过长时间的各种项目开发与迭代，目前用于Dux各个系列产品中的底层应用，框架遵循简单易用的原则，不过多封装多余组件进行开发，力求为开发者提供快速开发高质量应用体验。

# 特点

- 单入口模式
- 友好路由
- MHVC架构
- Api请求处理
- Cli模式支持
- Composer支持
- 友好异常管理
- 简单可扩展的模板引擎
- 简单易用的数据模型
- 多种缓存引擎
- NoSql存储引擎
- 图片处理
- 无限分类
- Http请求
- 数据验证
- 客户端信息
- 数据分页
- 图片验证码
- 多线程队列

# 引入组件
引入部分composer组件进行适应性封装

- intervention/image
- ralouphie/mimey
- endroid/qr-code
- phpfastcache/phpfastcache
- guzzlehttp/guzzle
- gregwar/captcha
- scrivo/highlight.php
- jenssegers/agent
- erusev/parsedown
- leafo/scssphp
- gkralik/php-uglifyjs
- overtrue/pinyin
- alchemy/zippy
- fukuball/jieba-php

# 环境

- 语言版本：PHP 7.2+
- 数据库版本：Mysql 5.6+
- WEB服务器：Apache/Nginx

# 文档

[中文开发使用文档](<https://duxphp.github.io/duxphp/>)

# 开源协议

[Apache License](https://github.com/duxphp/duxphp/blob/master/LICENSE)

> 本开源协议只适用于该框架，不包含框架的衍生产品，衍生产品请看对应的授权协议
# 讨论

QQ群：131331864

> 本系统非盈利产品，为防止垃圾广告和水群已开启收费入群，收费入群并不代表我们可以无条件回答您的问题，入群之前请仔细查看文档，常见安装等问题通过搜索引擎解决，切勿做伸手党
# bug反馈

issues反馈

# 1.x ~ 2.x 更新内容
- 统一框架内方法变量为严格模式
- 更新优化异常处理模块
- 独立发送类与文件管理类
- 清理无用冗余函数
- 优化数据过滤验证类
- 增加多线程队列处理
- 更新客户端类的部分方法为第三方类
- 更换验证码为第三方类
- 优化更新Mongo数据库类为ModelNo类
- 增加路由类入口又路由接管
- 增加多驱动日志类代替现有方法
- 增加依赖注入来更好的管理加载方法
- 优化配置类为多数组嵌套功能
- 更换图像处理驱动为 `intervention/image`
- 更换缓存驱动为 `phpfastcache/phpfastcache`

# 1.x ~ 2.x 废弃变更

- 删除部分冗余函数
- 删除Sql安装类统一到Tools类
- 删除上传类改为独立文件管理类库
- 删除缓存类变更为 `dux/com/Cache`
- 删除日志类变更为 `dux/com/log`
- 删除Cookie类使用原生代替
- 删除邮件类改为独立推送类库
- 删除拼音类统一到Tools类
- 删除分页类统一到Tools类
- 删除存储类统一使用缓存类
- 删除字符串处理类统一到函数库
- 删除Zip类统一到Tools类
- Http类取消 `doGet` 和 `doPost` 之外的请求方法
