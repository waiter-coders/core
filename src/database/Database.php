<?php
namespace waiterphp\core\database;

use waiterphp\core\database\connection\Pdo;
use waiterphp\core\database\Query\Query;
/*
 * 查询构建器
 *
 * 该工具主要的目的是为了给上层提供更加实用友好的sql操作方式。
 *
 * 该类沿用目前比较流行的连续子查询方式
 *
 *
 *
 * 事务，读写分离的设置（连接组，更新主，选择从，多主，事务主）
 *
 *
 */

class Database
{
    // 配置名
    public static $name = 'database';

    // 数据库参数
    private static $config = [];
    private static $connection = [];
    private static $defaultName = 'default';

    public static function connection($name = null)
    {
        $name = empty($name) ? self::$defaultName : $name;
        assert_exception(isset(self::$config[$name]), 'not has connection config:' . $name);
        if (!isset(self::$connection[$name])) {
            self::$connection[$name] = new Pdo(self::$config[$name]);
        }
        return self::$connection[$name];
    }

    public static function table($table, $name = null)
    {
        $name = empty($name) ? self::$defaultName : $name;
        assert_exception(isset(self::$config[$name]), 'not has connection config or default config:' . $name);
        return new Query($table, $name);
    }

    public static function configValue($name, $key)
    {
        return self::$config[$name]['write'][0][$key];
    }

    // 绑定事务区域
    public static function transaction(callable $method, $name = null)
    {
        try {
            self::connection($name)->beginTransaction();
            $result = $method();
            self::connection($name)->commit();
            return $result;
        } catch (\Exception $exception) {
            self::connection($name)->rollBack();
            throw $exception;
        }
    }

    public static function register($config, $name = 'default')
    {
        // 多数据库配置特殊处理
        if (isset(current($config)['database'])) {
            foreach ($config as $itemName=>$itemConfig) {
                self::register($itemConfig, $itemName);
            }
            return true;
        }

        // 单数据库配置
        assert_exception(isset($config['host']) && isset($config['database']), 'no host or database set');
        self::$config[$name] = self::formatConfig($config);
        if (isset($config['isDefault']) && $config['isDefault'] == true) {
            self::$defaultName = $name;
        }
        return true;
    }

    private static function formatConfig($config)
    {
        if (!isset($config['driver'])) {
            $config['driver'] = 'mysql';
        }
        if (!isset($config['port'])) {
            $config['port'] = 3306;
        }
        if (!isset($config['charset'])) {
            $config['charset'] = 'utf8';
        }
        if (!isset($config['username'])) {
            $config['username'] = 'root';
        }
        if (!isset($config['password'])) {
            $config['password'] = '';
        }
        $read = [];
        if (isset($config['read'])) {
            $read = $config['read'];
            unset($config['read']);
        }
        $servers = ['read'=> [], 'write'=>[$config]];
        $servers['read'] = [];
        if (!empty($read)) {
            foreach ($read as $host) {
                $servers['read'][] = array_merge($config, ['host'=>$host]);
            }
        }
        return $servers;
    }
}