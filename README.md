### waiterphp 核心类库

提供php的一些基础操作类库。

#### 设置环境

```php
set_env('app_name', 'waiterphp_core');
print get_env('app_name');
```

#### 数据库访问

```
set_env('database.default', []);
table('user')->where()->orderBy()->limit()->offset()->fetchAll();
```

#### 缓存访问

```php
set_env('cache.redis', []);
cache('redis')->hmget('some_key');
```

#### 访问获取

```php
request()->hostname();
```

#### curl请求

```php
curl($url, $params, $httpType, $header);
```


