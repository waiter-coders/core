<?php
namespace Waiterphp\Core\DB;

use Waiterphp\Core\DB\Connection\PdoDatabaseInstance as PdoDatabaseInstance;
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
/*
 * 关系数据库的访问管理类
 *
 * php总是需要访问关系型数据库的。
 * 当前环境，php + (mysql/mariaDB) 已经成为经典，其他的关系型数据库，如PostgreSQL等，也在蓬勃发展中。
 * 虽然，php提供了pdo（PHP Data Objects）兼容不同的关系型数据库，但php源码在连接、获取数据、操作数据等方面还是比较繁杂的。
 * 而且，关于数据连接信息、连接管理、数据缓存等，也需要我们封装起来，以便于我们在此类问题上有比较大的操作空间。
 * 这便是Database类产生的原因。
 *
 * 该类具有以下一些特点。
 * 1. 设置get、connect两个接口。这样，一次connect后便可以在任意地方，通过get方式获取到该连接。
 * 2. 用名字去标识一条连接，方便识别连接。
 *
 *该类中采用的命名是基于以下考虑：
 * 1. Database 此命名比Connection更能体现关系数据库的特性，在如今多种类型数据库横行的年代，我更愿意去选择具体的命名，
 *    而且由于它在数据库领域占领时间长，比较经典，就暂时占用了database的名字了
 * 2. 连接提供execute、fetchAll、fetchColumn等形式，是为了和源码的接口尽可能的相似
 */

class Database
{
    private static $config = array();
    private static $connection = array();
    private static $defaultName = 'default';


    public static function connect($config, $name = null)
    {
        $name = empty($name) ? self::$defaultName : $name;
        self::register($config, $name);
        return self::connection($name);
    }

    public static function connection($name = null)
    {
        $name = empty($name) ? self::$defaultName : $name;
        assert_exception(isset(self::$config[$name]), 'not has connection config:' . $name);
        if (!isset(self::$connection[$name])) {
            self::$connection[$name] = new PdoDatabaseInstance(self::$config[$name]);
        }
        return self::$connection[$name];
    }

    public static function table($table, $name = null)
    {
        $name = empty($name) ? self::$defaultName : $name;
        assert_exception(isset(self::$config[$name]), 'not has connection config or default config:' . $name);
        return new Query($table, $name);
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
        $read = array();
        if (isset($config['read'])) {
            $read = $config['read'];
            unset($config['read']);
        }
        $servers = array('read'=>array(), 'write'=>array($config));
        $servers['read'] = array();
        if (!empty($read)) {
            foreach ($read as $host) {
                $servers['read'][] = array_merge($config, array('host'=>$host));
            }
        }
        return $servers;
    }
}