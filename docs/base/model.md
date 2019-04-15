# 数据模型

## 说明

框架对数据库常用操作封装增删查改等操作，继承该类可以方便进行数据处理，默认以类名为数据表名，大驼峰会自动转换为下划线表名，如类名为"UserLogModel"则会自动转换当前操作表"dux_user_log"，"dux_"为数据表前缀

## 配置

在"global.php"中配置数据库信息

```php
return [
  // 数据库配置
  'dux.database' => [
    // 默认配置
    'default' => [
      'type' => 'mysql',             // 数据库类型，只含mysql驱动
      'host' => '127.0.0.1',         // 数据库主机
      'port' => '3306',              // 数据库端口
      'dbname' => 'duxphp',          // 数据库名
      'username' => 'root',          // 数据库用户名
      'password' => 'root',          // 数据库密码
      'prefix' => 'dux_',            // 数据表前缀
      'charset' => 'utf8mb4',        // 数据库编码
    ],
  ]
  ...
];
```



## 定义

典型的接口类如下：

```php
<?php
namespace app\index\index\model;

class IndexModel extends \dux\kernel\Model {

		public function __construct() {
        parent::__construct();
    }
    
}
```

框架会解析Url执行相应接口类：

```
http://域名/a/index/index/index
```

其中"a"为api层配置名称，可以通过配置进行更改，非默认层Url需要带入层名

## 方法

继承父类后可以使用以下的对象方法

- 查询多条数据

  说明：查询指定表多条，如果不包含table方法则会指向当前模型表

  返回：返回数组数据

  参数：

  - $table：表名，操作数据表名，不含表前缀
  - $field：字段，多个字段使用数组分割
  - $where：条件，二位数组查询条件
  - $bindParams：可选绑定条件数据，pdo数据绑定，一般不需要设置，模型会自动处理绑定
  - $order：排序条件，Sql排序语句，多个条件使用 "," 分割
  - $limit：查询条数，指定范围使用 "," 分割2个数字
  - $group：分组查询字段
  - lock：数据行锁，只支持InnoDB数据表

  ```php
  $this->table($table)
       ->field($field = [])
       ->where($where = [], $bindParams = [])
       ->order($order)
       ->limit($limit)
       ->group($group)
       ->lock()
       ->select();
  ```

  演示：

  ```php
  $this->table('article')
       ->field(['id', 'time'])
       ->where([
         'status' => 1,
       ])
       ->order('time desc')
       ->limit(10)
       ->select();
  ```

- 连表查询

  说明：join连表查询多条数据

  返回：返回数组数据

  参数：

  - $table：表名，操作数据表名，不含表前缀表名后请用 "()" 指定别名，如："article(A)"
  - $table2：链表表名，如："user(B)"
  - $condition：链表字段名，绑定当前表某个对应字段，如："A.user_id"
  - $condition2：链表字段名，如："B.user_id"，A 表 user_id 字段等于 B 表 user_id 字段
  - $type：链表类型，可用"<"、">"、"<>"、"><"，代表 "left join"、"right join"、"full join"、"inner join"，不指定默认为"inner join"
  - $where：条件，二位数组查询条件
  - $bindParams：pdo参数绑定

  ```php
  $this->table($table)
       ->join($table2, [$condition, $condition2], $type)
       ->where($where = [], $bindParams = [])
       ->select();
  ```

  演示：

  ```php
  $this->table('article(A)')
       ->join('user(B)', ['A.user_id', 'B.user_id'])
       ->where([
         'A.status' => 1,
       ])
       ->select();
  ```

- 查询单条数据

  说明：查询单条数据，连贯方法同多条一致

  ```
  $this->table($table)->where($data = [])->find();
  ```

  演示：

  ```php
  $this->table('article')->where(['id' => 1])->find();
  ```

- 统计查询数量

  说明：查询单条数据，连贯方法同多条一致

  返回：返回数量

  ```
  $this->table($table)->where($data = [])->count();
  ```

  演示：

  ```php
  $this->table('article')->where(['status' => 1])->count();
  ```

- 合计字段数量

  说明：相加合计某个字段数据

  返回：返回合计数字

  参数：

  - $field：字段名，数据表字段名

  ```
  $this->table($table)->where($data = [])->sum($filed);
  ```

  演示：

  ```php
  $this->table('article')->where(['status' => 1])->sum('num');
  ```

- 更新数据

  说明：更新一条数据

  返回：返回true或false

  参数：

  - $table：表名，操作数据表名，不含表前缀
  - $where：条件，更新数据条件
  - $data：数据，插入数据表数据

  ```
  $this->table($table)->data($data = [])->where($where = [])->update();
  ```

  演示：

  ```php
  $this->table('article')
       ->where([
         'id' => 1
       ])
       ->data([
         'content' => '内容',
       ])
       ->update();
  ```

- 删除数据

  说明：删除一条数据

  返回：返回true或false

  参数：

  - $table：表名，操作数据表名，不含表前缀
  - $where：条件，删除数据条件

  ```
  $this->table($table)->where($where)->delete();
  ```

  演示：

  ```php
  $this->table('article')->where(['id' => 1])->delete();
  ```

- 执行Sql语句

  说明：执行一条原生Sql语句，使用"{pre}"时将会自动转换为表前缀

  返回：返回查询结果

  参数：

  - $sql：Sql语句
  - $params：pdo绑定数据

  ```
  $this->query($sql, $params = []);
  ```

  演示：

  ```php
  $this->query('select * from {pre}article');
  ```

## 条件

模型封装了一些针对条件数组的方法，让结构更加清晰，部分语法处理参考 "[medoo]([https://medoo.in](https://medoo.in/))" 数据框架，所有的条件语句都可以组合嵌套使用

- 基础判断

  可以使用 ">"、"<" 、 ">="、"<="、"!" 来做基础判断，也可以使用 "<>" 和 "><" 来做范围判断，判断符在结尾处放在"[]"内

  ```php
  // WHERE id = 1
  $this->where([
    'id' => 1
  ])->select();
  
  // WHERE id > 1
  $this->where([
    'id[>]' => 1
  ])->select();
  
  // WHERE id >= 1
  $this->where([
    'id[>=]' => 1
  ])->select();
  
  // WHERE id <> 1
  $this->where([
    'id[!]' => 1
  ])->select();
  
  // WHERE id BETWEEN 1 AND 10
  $this->where([
    'id[<>]' => [1, 10]
  ])->select();
  
  // WHERE id NOT BETWEEN 1 AND 10
  $this->where([
    'id[><]' => [1, 10]
  ])->select();
  ```

- AND OR 条件语句

  多个条件可以使用 "AND" 或者 "OR" 作为键名，可以进行多重嵌套

  ```php
  $this->where([
    'AND' => [
      'user_id' => 1,
      'id[>]' => 2,
    ],
    'OR' => [
      'user_id' => 1,
      'id[>]' => 2,
      ‘AND' => [
        'user_id' => 3,
      ]
    ],
  ])->select();
  ```

  如果为多个 "AND" 或者多个 "OR" 并行可以使用注释 "#" 进行区分

  ```php
  $this->where([
    'OR #one' => [
      'user_id' => 1,
    ],
    'OR #tow' => [
      'user_id' => 2,
    ],
  ])->select();
  ```

- LIKE 模糊查询

  模型封装了 "LIKE" 与 "NOT LIKE" 条件

  ```php
  // WHERE name LIKE '%dux_%'
  $this->where([
    'name[~]' => 'dux_',
  ])->select();
  ```

- REGEXP 正则查询

  字段后使用"[REGEXP]"可以设置正则条件

  ```php
  // WHERE name REGEXP '[a-z0-9]*'
  $this->where([
    'name[REGEXP]' => '[a-z0-9]*',
  ])->select();
  ```

- IN 语句查询

  如果查询内容为数组则自动使用 "IN" 或 "NOT IN" 查询

  ```php
  // WHERE id IN (1, 2, 3)
  $this->where([
    'id' => [1, 2, 3],
  ])->select();
  
  // WHERE id NOT IN (1, 2, 3)
  $this->where([
    'id[!]' => [1, 2, 3],
  ])->select();
  ```

- Sql 条件查询

  少数情况下需要编写原生 Sql 条件进行查询，使用 "_sql" 作为键名，多个条件请使用数组

  ```php
  $this->where([
    '_sql' => 'id > 1 AND id < 10',
  ])->select();
  
  $this->where([
    '_sql' => [
    	'id > 1 AND id < 10',
    	'name LIKE "dux_%"'
    ],
  ])->select();
  ```

## 数据

模型封装常用数据逻辑运算，可以针对数据进行自动处理

- 逻辑运算符

  更新数据时可使用常用加减乘除，分别为 "+"、"-"、"*"、"/"

  ```php
  // SET num + 1
  $this->where([
    'id' => 1,
  ])->data([
    'num[+]' => 1
  ])->update();
  
  // SET num / 1
  $this->where([
    'id' => 1,
  ])->data([
    'num[/]' => 1
  ])->update();
  ```

- json数组

  如果数据值为数组则会自动转义为json字符串

  ```php
  $this->data([
    'data' => [1, 2, 3]
  ])->insert();
  ```

  