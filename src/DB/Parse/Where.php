<?php
namespace Waiterphp\Core\DB\Parse;
/*
 * 条件解析
 * 用于把条件数组转化为sql语句
 */
class Where
{
    private static $actionMap = [
        'gt'=>'>',
        'lt'=>'<'
    ];

    public static function parse(array $where)
    {
        $sql = [];
        $params = [];
        foreach ($where as $key => $value) {
            list($itemSql, $itemParams) = self::parseWhere(trim($key), $value);
            $sql[] = '(' . $itemSql . ')';
            $params = array_merge($params, $itemParams);
        }
        $sql = implode(' and ', $sql);
        return [$sql, $params];
    }

    private static function parseWhere($key, $value)
    {
        // 纯value方式
        if (is_numeric($key)) {  
            return [$value, []];
        }
        // key-value方式
        $value = !is_array($value) ? [$value] : $value; // 参数都转化为数组，方便参数间的合并
        $valueCount = count($value);
        $sql = self::parseWhereSql($key, $valueCount);
        assert_exception(substr_count($sql, '?') == $valueCount, 'param num error:' . $sql . json_encode($value));
        return [$sql, $value];
    }

    private static function parseWhereSql($key, $valueCount)
    {
        // 源码方式
        if (strpos($key, '?') !== false) { 
            return $key;
        }
        // 字段、操作符方式
        list($column, $action) = explode(' ', $key . ' ', 2);
        $action = self::formatAction($action, $valueCount);
        // 对in、between做特殊处理
        if ($action == 'in' || $action == 'not in') {
            $query = implode(',', array_fill(0, $valueCount, '?'));
            return sprintf('%s %s (%s)', $column, $action, $query);
        }
        if ($action == 'between') {
            return sprintf('%s between ? and ?', $column);
        }
        return sprintf('%s %s ?', $column, $action);
    }

    private static function formatAction($action, $valueCount)
    {
        $action = trim($action);
        if ($action == '' && $valueCount > 1) {
            return 'in';
        }
        if ($action == '') {
            return '=';
        }
        if (isset(self::$actionMap[$action])) {
            return self::$actionMap[$action];
        }
        return $action;
    }
}