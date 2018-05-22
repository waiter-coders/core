<?php
/*
 * 基础功能函数
 */
// 不同于array_merge_recursive，array_merge_cover相同键名时，后者覆盖前者
function array_merge_cover($baseArray, $mergeArray)
{
    foreach ($mergeArray as $key=>$value) {
        if (is_array($value)) {
            !isset($baseArray[$key]) && $baseArray[$key] = array();
            $baseArray[$key] = array_merge_cover($baseArray[$key], $value);
        } else {
            $baseArray[$key] = $value;
        }
    }
    return $baseArray;
}

// 检查是否是dot结构
function isDot($dot)
{
    return (is_string($dot) && strpos($dot, '.') > 0);
}

// 把 controller.home.show 类型的转化为ControllerHome类和方法 show
function dotToMethod($dot)
{
    $class = explode('.', $dot);
    $method = array_pop($class);
    return array(dotToClass($class), $method);
}

// 把 controller.home类型的转化为ControllerHome类
function dotToClass($dot)
{
    $class = is_array($dot) ? $dot : explode('.', $dot);
    foreach ($class as $key=>$value) {
        $class[$key] = ucfirst($value);
    }
    return '\\' . implode('\\', $class);
}

// 根据dot键名获取数组数据
function getDataByDot($data, $dot)
{
    // 一层一层搜索键值数组
    $dot = explode('.', $dot);
    foreach ($dot as $key) {
        assertOrException(isset($data[$key]), 'has no item:' . $key);
        $data = $data[$key];
    }
    return $data;
}

function assertOrException($boolean, $errorMessage, $code = 500)
{
    if (!$boolean) {
        throw new Exception($errorMessage, $code);
    }
}

function lowerToUpper($class)
{

}

// 语法糖
function table($table, $name = 'default') // 数据库访问
{
    return \Waiterphp\Core\DB::table($table, $name);
}