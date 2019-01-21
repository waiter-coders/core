<?php
namespace Waiterphp\Core\Env;

use Waiterphp\Core\Dot\Dot as Dot;

class Factory
{
    private static $alias = array(); // 类别名
    private static $instance = array(); // 单例类

    // 单例工具
    public static function instance($class, $params = array())
    {
        $class = (strpos($class, '.') > 0) ? Dot::dotToClass($class) : $class;
        if (isset(self::$instance[$class])) {
            return self::$instance[$class];
        }
        self::$instance[$class] = self::factory($class, $params); // 生产对象
        return self::$instance[$class];
    }

    public static function factory($class, $params = array())
    {
        $class = (strpos($class, '.') > 0) ? Dot::dotToClass($class) : $class;
        return empty($params) ? new $class() : new $class($params);
    }

    public static function action($action, $params)
    {
        return call_user_func($action, $params);
    }
}