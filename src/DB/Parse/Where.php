<?php
namespace Waiterphp\Core\DB\Parse;
/*
 * 条件解析
 * 用于把条件数组转化为sql语句
 */
class Where
{
    public static function parse(array $where)
    {
        $sql = array();
        $params = array();
        foreach ($where as $key => $value) {
            list($itemSql, $itemParams) = self::parseWhere(trim($key), $value);
            $sql[] = '(' . $itemSql . ')';
            $params = array_merge($params, $itemParams);
        }
        $sql = implode(' and ', $sql);
        return array($sql, $params);
    }

    private static function parseWhere($key, $value)
    {
        if (is_numeric($key)) { // 无参数 
            return array($value, array());
        }
        $value = !is_array($value) ? array($value) : $value; // 参数都转化为数组，方便参数间的合并
        $where = self::parseItemWhere($key, $value);
        assert_exception(substr_count($where, '?') == count($value), 'param num error:' . $where. json_encode($value));
        return array($where, $value);
    }

    private static function parseItemWhere($key, $value)
    {
        if (strpos($key, '?')) { // 源码方式
            return $key;
        }
        list($column, $action) = explode(' ', $key . ' ', 2);
        $action = self::formatAction($action, $value);
        if ($action == 'in' || $action == 'not in') {
            $query = implode(',', array_fill(0, count($value), '?'));
            return sprintf('%s %s (%s)', $column, $action, $query);
        }
        if ($action == 'between') {
            return sprintf('%s between ? and ?', $column);
        }
        return sprintf('%s %s ?', $column, $action);
    }

    private static function formatAction($action, $value)
    {
        $action = trim($action);
        if ($action == '' && count($value) > 1) {
            return 'in';
        }
        if ($action == '') {
            return '=';
        }
        return $action;
    }
}