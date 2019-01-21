<?php

class Dot
{
    // 检查是否是dot结构
    public static function isDot($dot)
    {
        return (is_string($dot) && strpos($dot, '.') > 0);
    }

    // 把 controller.home.show 类型的转化为ControllerHome类和方法 show
    public static function dotToMethod($dot)
    {
        $class = explode('.', $dot);
        $method = array_pop($class);
        return array(dotToClass($class), $method);
    }

    // 把 controller.home类型的转化为ControllerHome类
    public static function dotToClass($dot)
    {
        $class = is_array($dot) ? $dot : explode('.', $dot);
        foreach ($class as $key=>$value) {
            $class[$key] = ucfirst($value);
        }
        return '\\' . implode('\\', $class);
    }

    // 根据dot键名获取数组数据
    public static function findDataByDot($dot, $data)
    {
        // 一层一层搜索键值数组
        $dot = explode('.', $dot);
        foreach ($dot as $key) {
            assert_or_exception(isset($data[$key]), 'has no item:' . $key);
            $data = $data[$key];
        }
        return $data;
    }
    
}