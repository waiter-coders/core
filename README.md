### waiterphp 核心类库

该项目提供了php的一些基础操作封装。可直接采用composer装载该类。

以下是类库的相关功能介绍。

#### 装载配置
可通过以下方式获取配置文件中的数据。
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
set_env('database', [
	'default'=>[
		'username'=>'test'
	]
]);
```
也可以直接写入数组：
```php
set_env(['database'=>['default'=>[
	'host'=>'localhost',
	'username'=>'root',
	'password'=>'',
	'database'=>'tests'
]]])
```
注意：
> 重复设置的环境变量，后者会覆盖前者
> 环境变量有一些默认键名，如database为数据库设置，cache为缓存设置,函数会检测该键名，并自动初始化到相关类库


#### 访问数据库

```php
// 设置数据库配置，可通过load_configs从文件直接装载
set_env('database.default', [
	'host'=>'127.0.0.1', 
	'username'=>'root', 
	'password'=>'', 
	'database'=>'tests'
]);

// 获取多行数据
$data = table('article')->select('articleId,userId,title,hit as hit_num')->where([
	'userId'=>1,
	'articleId'=>[1,2,3,4,5,6,7,8],
	'addTime >='=>'2018-01-01 00:00:00',
	'title like'=>'%测试%'
])->orderBy('articleId desc')
->limit(10)
->offset(0)->fetchAll();

// 获取单行
$data = table('article')->where(['userId'=>1])->fetchRow();

// 写入数据
$articleId = table('article')->insert([
	'userId'=>2,
	'title'=>'insert data'
]);

// 删除数据
table('article')->where([
	'articleId'=>$articleId
])->delete();

// 更新数据
table('article')->where([
	'articleId'=>1
])->update([
	'hit'=>211
]);

// 表达式更新
 table('article')->where([
	'articleId'=>1
])->update([
	'hit=hit+1'
]);

// 统计 
table('article')->where(['userId'=>1])->count();

// 分组
table('article')->select('userId,count(*)')->groupBy('userId')->fetchAll();
```
#### 访问缓存

```php
//file


// redis
set_env('cache.redis', []);
cache('redis')->hmget('some_key');

// memcache

```

#### 页面渲染工具
```php
set_env('view', []);
echo render('user/login.html', ['username'=>'测试'], 'smarty');
```
> 第三个参数可以选择你采用的渲染引擎，默认为smarty。
> 目前可支持的有：smarty、twig

可以设置自定义的第三个参数，要求类本身实现render方法。如下：
```php
set_env('view', []);
render('user/login.html', ['username'=>'测试'], 'tools.myView');
```

> 页面渲染需在您自己的项目composer.json中加入相关类库，

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

#### 异常检测
用法如下：
```php
assert_exception(false, '程序异常！', 500);
```
#### 事件绑定和触发
```php
bind_to_env($tab, $action);
env_trigger($tab, $params = []);
```
#### 构建工具
```php
build();
```

#### http请求

```php
print request()->hostname();
print request()->query();
print request()->query('userId');
print request()->post();
print request()->post('userId');
```

#### curl请求

```php
curl($url, $params, $httpType, $header);
```

#### 文件操作
```php
get_files('/home/user');
write('/home/user/test.txt', $content, 'a+');
```