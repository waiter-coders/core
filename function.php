<?php
/*
 * 基础功能函数
 */

// 不同于array_merge_recursive，array_merge_recursive_cover相同键名时，后者覆盖前者
function array_deep_cover($baseArray, $mergeArray)
{
    assertOrException(is_array($baseArray), 'baseArray input is not array:' . json_encode($baseArray));
    assertOrException(is_array($mergeArray), 'mergeArray input is not array:' . json_encode($mergeArray));
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

// 断言正确，否则抛出异常
function assertOrException($boolean, $errorMessage, $code = 500)
{
    if (!$boolean) {
        throw new \Exception($errorMessage, $code);
    }
}

/*
 * 点的相关功能
 */
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
function findDataByDot($dot, $data)
{
    // 一层一层搜索键值数组
    $dot = explode('.', $dot);
    foreach ($dot as $key) {
        assertOrException(isset($data[$key]), 'has no item:' . $key);
        $data = $data[$key];
    }
    return $data;
}


/*
 * 应用功能
 */

// 表访问
function run($dotAction, $params)
{
    list($class, $method) = dotToMethod($dotAction);
    $object = new $class($params);
    return call_user_func_array(array($object, $method), array());
}

function table($table, $name = 'default') // 数据库访问
{
    return \Waiterphp\Core\DB::table($table, $name);
}

function container($environment)
{
    return \Waiterphp\Core\Env::instance($environment);
}

function loadConfig($files, $basePaths)
{
    return \Waiterphp\Core\Config::loadFiles($files, $basePaths);
}
