<?php
namespace Waiterphp\Core\DB\Parse;

/*
 * Join链接对象，用于提供Join方法
 */
class Update
{
    public static function formatData($data)
    {
        $sql = [];
        $params = [];
        foreach ($data as $key => $value) {
            list($subSql, $subParams) = self::formatValue($key, $value); 
            $sql[] = $subSql;
            $params = array_merge($params, $subParams);
        }
        return [implode(', ', $sql), $params];
    }

    private static function formatValue($key, $value)
    {
        if (is_numeric($key)) {
            return [$value, []];
        }
        return [$key . ' = ?', [$value]];
    }
}