### waiterphp 核心类库

该项目提供了php的一些基础操作封装。

#### 装载配置
可通过以下方式装载和获取配置文件
```php
$configs = load_configs($fileNames, $basePaths);
```
函数可以从basePaths设置的多个路径里面，加载fileNames里面设置的多个文件中的内容，覆盖顺序为后者覆盖前者。

#### 设置当前环境
通过以下函数设置环境变量和获取环境变量：
```php
set_env('app_name', 'waiterphp_core');
print get_env('app_name');
```

 可采用dot方式,如下两种写法作用相同:
 
```php
set_env('database.default.username', 'test');
set_env('database', array(
	'default'=>array(
		'username'=>'test'
	)
));
```
注意：
> 环境变量的设置，后者会覆盖前者
> 环境变量有一些默认键名，如database为数据库设置，cache为缓存设置,如cache.redis,cache.file. 更多设置见：

#### 异常检测
用法如下：
```php
assert_exception(false, '程序异常！', 500);
```

#### 访问数据库

```
// 设置数据库配置，可通过load_configs从文件直接装载
set_env('database.default', array(
	'host'=>'127.0.0.1', 
	'username'=>'root', 
	'password'=>'', 
	'database'=>'test'
));

// 获取多行数据
table('article')->where(array(
		'userId=>124,
	))->orderBy('articleId desc')
	->limit(10)
	->offset(0)
	->fetchAll();

// 获取单行
table('user')->where(array(
	'userId'=>134
))->fetchRow();
```

#### 访问缓存

```php
set_env('cache.redis', []);
cache('redis')->hmget('some_key');
```

#### http请求

```php
print request()->hostname();
```

#### curl请求

```php
curl($url, $params, $httpType, $header);
```

#### 文件操作
```php
file().getFiles('/home/tianzheng')
```

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
可以利用trait快速构建一个过滤器
```php
use Waiterphp\Core\Filter\FilterTrait;

class HttpRequest
{
	use Filter;
}
```
当然，你也可以直接使用Filter直接过滤数据
```php
filter($data)->getInt('userId', '');
```
注意：
>  第二个参数为默认值。如果没有设置，当获取不到数据时，抛出异常！

#### 构建工具
```php
build();
```

#### 页面渲染工具
```php
set_env('view', array());
echo render('user/login.html', array('username'=>'测试'), 'smarty');
```
> 第三个参数可以选择你采用的渲染引擎，默认为smarty。
> 目前可支持的有：smarty、twig
