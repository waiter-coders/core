<?php
namespace Waiterphp\Core;

class Container
{
    private static $alias = array(); // 类别名
    private static $instances = array(); // 单例类

    // 单例工具
    public static function instance($class, $params = array(), $topic = 'default')
    {
        $class = (strpos($class, '.') > 0) ? Dot::dotToClass($class) : $class;
        if (isset(self::$instances[$class][$topic])) {
            return self::$instances[$class][$topic];
        }
        self::$instances[$class][$topic] = self::factory($class, $params); // 生产对象
        return self::$instances[$class][$topic];
    }

    public static function factory($class, $params = array())
    {
        $class = (strpos($class, '.') > 0) ? Dot::dotToClass($class) : $class;
        return new $class($params);
    }
}