### waiterphp 核心类库

提供php的一些基础操作类库。

#### 设置环境

```php
set_env('app_name', 'waiterphp_core');
print get_env('app_name');
```

#### 装载配置
```php
$configs = load_configs($filePath);
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

#### http请求

```php
request()->hostname();
```

#### curl请求

```php
curl($url, $params, $httpType, $header);
```

#### 文件操作


#### 事件绑定和触发
```php
bind_to_env($tab, $action);
env_trigger($tab, $params = array());
```

#### dao的使用
```
use Waiterphp\Core\Dao\DaoTrait;

class User
{
	use DaoTrait;
}
```

#### 过滤器的使用
