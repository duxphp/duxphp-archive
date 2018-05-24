# mipush
easy to use mipush parallelly
并行的小米sdk
Version 0.1.1

## about
基于 https://github.com/TIGERB/easy-mipush 版本进行修改，修复部分bug问题，方便项目使用

### intro
- 调用一次同时推送给ios&android两种设备，不用分别推送
- 简化使用
    + 初始化设置Push:init('ios设置','android设置','通用设置','使用环境')
    + 调用推送方法Push::toUse('小米push接口名','接口参数')
- 目前只实现了按regid(登记id),alias(别名),user_account(用户账号),topic(标签), multi_topic(多标签),all(全体)推送

### how to use?
```
composer require duxphp/easy-mipush

使用格式：
try {
    Push::init(
        ['secret' => 'string,必传,ios密钥'], 
        ['secret' => 'string,必传,android密钥', 'package_name' => 'string,必传,android包名']
        [   
          'title'        => 'string,非必传,消息通知自定义title',
          'pass_through' => 'int,非必传,0通知栏消息,1透传,默认0',
          'notify_type'  => 'int,非必传,-1:默认所有,1:使用默认提示音提示,2:使用默认震动提示,4:使用默认led灯光提示',
          'time_to_send' => 'int,非必传,定时推送,单位毫秒,默认0',
        ],
        'string,develop开发环境，product生产环境, 默认develop'
        );  
    $res = Push::toUse('string,小米push方法名', 'array, 该方法对应的参数');
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo $e->getMessage();
}

使用示例：
use Mipush\Push;

require './vendor/autoload.php';

try {
    Push::init(
        ['secret' => 'test'], 
        ['secret' => 'test', 'package_name' => 'com.test'],
        [   
          'title'        => 'test',
          'pass_through' => 0,
          'notify_type'  => -1,
          'time_to_send' => 0,
        ],
        'develop'
        );  
    $res = Push::toUse('userAccount', [
            'user_account' => [1],
            'description'  => 'test'
        ]);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo $e->getMessage();
}
```


