<?php
/*
 * 基础功能函数
 * 
 * 采用下划线命名，和php基础函数统一
 */

 // 断言正确，否则抛出异常
function assert_exception($boolean, $errorMessage, $code = 500)
{
    if (!$boolean) {
        throw new \Exception($errorMessage, $code);
    }
}

// 不同于array_merge_recursive，array_merge_recursive_cover相同键名时，后者覆盖前者
function array_deep_cover($baseArray, $mergeArray)
{
    assert_exception(is_array($baseArray), 'baseArray input is not array:' . json_encode($baseArray));
    assert_exception(is_array($mergeArray), 'mergeArray input is not array:' . json_encode($mergeArray));
    foreach ($mergeArray as $key=>$value) {
        if (is_array($value)) {
            !isset($baseArray[$key]) && $baseArray[$key] = array();
            $baseArray[$key] = array_deep_cover($baseArray[$key], $value);
        } else {
            $baseArray[$key] = $value;
        } 
    }
    return $baseArray;
}

// 从文件在加载配置信息的快捷函数
function load_configs($fileNames, $basePaths)
{
    $config = array();
    $fileNames = is_string($fileNames) ? array($fileNames) : $fileNames;
    $basePaths = is_string($basePaths) ? array($basePaths) : $basePaths;
    foreach ($basePaths as $basePath) {
        foreach ($fileNames as $fileName) {
            $filePath = $basePath . DIRECTORY_SEPARATOR . $fileName;
            if (is_file($filePath)) {
                $targetConfig = require $filePath;
                assert_exception(is_array($targetConfig), 'config not return array:' . $filePath);
                $config = array_deep_cover($config, $targetConfig);
            }
        }
    }
    return $config;
}

// 工厂生产类的快捷函数
function factory($class, $params = array())
{
    return \Waiterphp\Core\Env\Factory::factory($class, $params);
}

// 工厂生产单例的快捷函数
function instance($class, $params = array())
{
    return \Waiterphp\Core\Env\Factory::instance($class, $params);
}

// 调用类方法的快捷函数
function action($action, $params, $isInstance = true)
{
    list($class, $method) = \Waiterphp\Core\Dot\Dot::dotToMethod($action);
    $object = $isInstance ? instance($class) : factory($class);
    return call_user_func_array(array($object, $method), $params);
}

// 设置当前环境信息的快捷函数
function set_env($key, $value)
{
    \Waiterphp\Core\Env\Context::instance()->set($key, $value);
}

// 获取当前环境信息的快捷函数
function get_env($key)
{
    return \Waiterphp\Core\Env\Context::instance()->get($key);
}

// 绑定事件到当前环境的快捷函数
function bind_to_env($tab, $action)
{
    \Waiterphp\Core\Env\Context::instance()->bind($tab, $action);
}

// 触发事件的快捷函数
function env_trigger($tab, $params = array())
{
    \Waiterphp\Core\Env\Context::instance()->trigger($tab, $params);
}

// 获取db类表对象的快捷函数
function table($table, $name = 'default') // 数据库访问
{
    return \Waiterphp\Core\DB\Database::table($table, $name);
}

function request()
{

}

function cache($cacheType)
{

}

function curl($url, $params = array(), $type = 'get', $header = array())
{
    return \Waiterphp\Core\Http\Curl::sendRequest($url, $params, $type, $header);
}

function filter($data)
{
    return \Waiterphp\Core\Filter\Filter::instance($data);
}

function build()
{

}

function render($template, $params = array(), $engine = 'smarty')
{

}