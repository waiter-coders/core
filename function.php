<?php
/*
 * 基础功能函数
 */

// 不同于array_merge_recursive，array_merge_recursive_cover相同键名时，后者覆盖前者
function array_merge_recursive_cover($baseArray, $mergeArray)
{
    foreach ($mergeArray as $key=>$value) {
        if (is_array($value)) {
            !isset($baseArray[$key]) && $baseArray[$key] = array();
            $baseArray[$key] = array_merge_recursive_cover($baseArray[$key], $value);
        } else {
            $baseArray[$key] = $value;
        }
    }
    return $baseArray;
}

// 断言正确，否则抛出异常
function assertOrException($boolean, $errorMessage, $code = 500)
{
    if (!$boolean) {
        throw new \Exception($errorMessage, $code);
    }
}

/*
 * 语法糖
 */

// 表访问
function table($table, $name = 'default') // 数据库访问
{
    return \Waiterphp\Core\DB::table($table, $name);
}