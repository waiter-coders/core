<?php
namespace waiterphp\core\database;

use waiterphp\core\database\connection\Selector;
use waiterphp\core\database\query\Query;
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
    public static function register($configs)
    {
        Selector::register($configs);
    }

    public static function table($table, $name = '')
    {
        return new Query($table, $name);
    }  

    public static function transaction(callable $method, $name = '')
    {
        $connection = Selector::select($name);
        try {            
            $connection->beginTransaction();
            $result = $method();
            $connection->commit();
            return $result;
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}