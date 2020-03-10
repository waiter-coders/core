<?php
/*
 * 基础功能函数
 * 
 * 采用下划线命名，和php基础函数统一
 */

 // 断言正确，否则抛出异常
function assert_exception($boolean, $errorMessage, $errorCode = 500)
{
    if ($boolean == false) {
        throw new \Exception($errorMessage, $errorCode);
    }
}

function assert_is_keys($array, $keys, $errorMessage, $errorCode = 500)
{
    assert_exception($array === $keys, $errorMessage, $errorCode);
}

function assert_has_keys()
{

}

// 不同于array_merge_recursive，array_merge_recursive_cover相同键名时，后者覆盖前者
function array_deep_merge($array_1, $array_2)
{
    assert_exception(is_array($array_1), 'arg 1 is not array');
    assert_exception(is_array($array_2), 'arg 2 is not array');
    foreach ($array_2 as $key=>$value) {
        if (is_array($value)) {
            if (!isset($array_1[$key])) { // 初始化子数组
                $array_1[$key] = [];
            }
            $array_1[$key] = array_deep_merge($array_1[$key], $value);
        } else {
            $array_1[$key] = $value;
        } 
    }
    return $array_1;
}

// dot class转为命名空间class
function dot_class($dot)
{
    $class = explode('.', $dot);
    // $class = array_map(function($name){
    //     return ucfirst($name);
    // }, $class);
    return '\\' . implode('\\', $class);
}

// dot method转为类、方法数组
function dot_method($dot)
{
    $class = explode('.', $dot);
    $method = array_pop($class);
    // $class = array_map(function($name){
    //     return ucfirst($name);
    // }, $class);
    return ['\\' . implode('\\', $class), $method];
}

// 从文件在加载配置信息的快捷函数
function load_configs($files)
{
    $config = [];
    $files = is_string($files) ? [$files] : $files;
    foreach ($files as $file) {
        if (!is_file($file)) {
            throw new \Exception('not has file:' + $file);
        }
        $fileConfig = require $file;
        $config = array_deep_merge($config, $fileConfig);
    }
    return $config;
}

// 单例
function instance($class, $params = [])
{
    static $instance = []; // 单例类
    // 标准化class
    if (strpos($class, '.') > 0) {
        $class = dot_class($class);
    }
    if (!isset($instance[$class])) {
        $instance[$class] = empty($params) ? new $class() : new $class($params);
    }
    return $instance[$class];
}

// 环境上下文
function context()
{
    return instance('waiterphp.core.Context');
}

// 获取db类表对象的快捷函数
function table($table, $name = 'default') // 数据库访问
{
    return \waiterphp\core\database\Database::table($table, $name);
}

// 调用远程http服务
function curl($url, $params = [], $type = 'get', $header = [])
{
    return \waiterphp\core\Http\Curl::sendRequest($url, $params, $type, $header);
}

function format_keys($array, $keys, $canEmpty = false)
{
    
}