# 无限分类

该类用于获取树形分类与递归分类

## 使用

```php
// 字段映射 "当前id"、"上级id"、"分类名"、"树形符分类名"
$field = ['id', 'pid', 'title', 'fulltitle'];
$cat = new \dux\lib\Category($field);

// 示例数据源
$data = [
	['id' => 1, 'pid' => 0, 'title' => '顶级1'],
	['id' => 2, 'pid' => 0, 'title' => '顶级2'],
	['id' => 3, 'pid' => 1, 'title' => '子类1'],
	['id' => 4, 'pid' => 2, 'title' => '子类1'],
];
```

## 方法

### 获取树形分类

- 参数：

  $data：源数据

  $id：起始上级id

```php
$cat->getTree(array $data, int $id = 0);
```

### 获取下级分类

- 参数：

  $pid：上级id

  $data：源数据

```php
$cat->getChild(int $pid, array $data = []);
```

### 获取分类路径

- 参数：

  $data：源数据

  $id：当前id

```php
$cat->getPath(array $data, int $id);
```

