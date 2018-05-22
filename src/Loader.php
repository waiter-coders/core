<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/22
 * Time: 15:51
 */

namespace Waiterphp\Core;


class Loader
{
    private static $_autoload = array(); // 全局加载类路径

    // 装载类文件
    public static function load($class)
    {
        $layer = self::extractLayer($class); // 获取类所在域
        $layer = empty($layer) ? 'Lib' : $layer; // 域为空则默认为引擎域
        if (!isset(self::$_autoload[$layer])) {
            return false;
        }
        $classPath =  self::extractPath($class); // 获取类的相对路径
        foreach (self::$_autoload[$layer] as $root) {
            $file = $root . '/' . $classPath;
            if (is_file($file)) {
                return require $file; // 加载文件
            }
        }
        return false;
    }

    // 添加加载路径信息
    public static function addLayer($layer, $paths)
    {
        $paths = is_array($paths) ? $paths : array($paths);
        if (!isset(self::$_autoload[$layer])) {
            self::$_autoload[$layer] = $paths;
        } else {
            self::$_autoload[$layer] = array_merge($paths, self::$_autoload[$layer]);// 越后进入的越先被查找
        }
        return true;
    }

    // 获得相对类路径
    private static function extractPath($class)
    {
        $paths = explode('\\', $class);
        count($paths) > 1 && array_shift($paths); // 去掉非lib域所占地址
        return implode('/', $paths) . '.php';
    }

    // 获取顶级类域
    private static function extractLayer($class)
    {
        return ucfirst(substr($class, 0, strpos($class, '\\')));
    }
}

