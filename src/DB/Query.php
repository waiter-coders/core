<?php
namespace Waiterphp\Core\DB;

use Waiterphp\Core\DB\Parse\Where as DB_Where;
use Waiterphp\Core\DB\Parse\Join as DB_Join;
use Waiterphp\Core\DB\Parse\Update as DB_Update;

class Query
{
    public $connection = 'default';
    public $columns = '*';
    public $mainTable = null;
    public $join = '';
    public $where = [];
    public $groupBy = '';
    public $having ='';
    public $orderBy = '';
    public $limit = 10000; // 默认限制，最大一万条
    public $offset = 0;

    public function __construct($table, $connection)
    {
        $this->mainTable = $table;
        $this->connection = $connection;
    }

    /*
     * 连续查询的相关方法
     */
    public function select($columns)
    {
        $this->columns = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    public function where($where)
    {
        $this->where = $where;
        return $this;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    public function having($having)
    {
        $this->having = $having;
        return $this;
    }

    public function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = (int) $offset;
        return $this;
    }

    public function partBy($column, callable $method)
    {
        $pattern = '/' . $column . ' = (\d+)/i';
        $hasColumn = preg_match($pattern, $this->where, $matches);
        assert_exception($hasColumn, 'can not find part column ' . $column);
        $partId = $matches[1];
        $suffix = $method($partId);
        $this->mainTable .= '_' . $suffix;
        return $this;
    }

    /*
     * 获取数据的相关方法
     */
    public function fetchRow()
    {
        $this->limit = 1;
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchRow($sql, $params);
    }

    public function fetchAll()
    {
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchAll($sql, $params);
    }

    public function fetchColumn($column)
    {
        $this->select($column);
        $this->limit = 1;
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchColumn($sql, $params);
    }

    public function fetchColumns($column)
    {
        $this->select($column);
        $list = $this->fetchAll();
        $return = [];
        foreach ($list as $record) {
            $return[] = $record[$column];
        }
        return $return;
    }

    public function count($column = '*')
    {
        return $this->fetchColumn('count('.$column.') as num');
    }

    public function max($column)
    {
        return $this->fetchColumn('max('.$column.') as num');
    }

    public function min($column)
    {
        return $this->fetchColumn('min('.$column.') as num');
    }

    public function avg($column)
    {
        return $this->fetchColumn('avg('.$column.') as num');
    }

    public function sum($column)
    {
        return $this->fetchColumn('sum('.$column.') as num');
    }

    public function exists()
    {
        $num = $this->count('*');
        return empty($num) ? false : true;
    }

    public function generateQuery()
    {
        $sql = 'select ' . $this->columns . ' from ' . $this->mainTable . $this->join;
        list($where, $queryParams) = DB_Where::parse($this->where);
        if (!empty($where)) {
            $sql .= ' where ' . $where;
        }
        if (!empty($this->groupBy)) {
            $sql .= ' group by ' . $this->groupBy;
        }
        if (!empty($this->groupBy) && !empty($this->having)) {
            $sql .= ' having ' . $this->having;
        }
        if (!empty($this->orderBy)) {
            $sql .= ' order by ' . $this->orderBy;
        }
        $sql .= ' limit ' . $this->offset . ',' . $this->limit;
        return [$sql, $queryParams];
    }

    /*
     * join连接的相关方法
     */
    public function leftJoin($table)
    {
        return new DB_Join($this, $table, 'left');
    }

    public function rightJoin($table)
    {
        return new DB_Join($this, $table, 'right');
    }

    public function innerJoin($table)
    {
        return new DB_Join($this, $table, 'inner');
    }

    public function fullJoin($table)
    {
        return new DB_Join($this, $table, 'full');
    }

    /*
     * 操作数据的相关方法
     */
    public function insert($data)
    {
        $columns = implode(',', array_keys($data));
        $values = implode(',', array_fill(0, count($data), '?'));
        $sql = sprintf('insert into %s (%s) values (%s)', $this->mainTable, $columns, $values);
        Database::connection($this->connection)->execute($sql, array_values($data));
        return Database::connection($this->connection)->lastInsertId();
    }

    public function update($data)
    {
        assert_exception(!empty($this->where), 'please set where when update');
        list($where, $queryParams) = DB_Where::parse($this->where);
        list($updateSql, $updateParams) = DB_Update::formatData($data);
        $sql = 'update ' . $this->mainTable . ' set ' . $updateSql . ' where ' . $where;
        $params = array_merge($updateParams, $queryParams);
        Database::connection($this->connection)->execute($sql, $params);
        return Database::connection($this->connection)->lastAffectRows();
    }

    public function increment($column, $num = 1)
    {
        return $this->update([
            $column . ' = ' . $column . ' + ' . $num
        ]);
    }

    public function decrement($column, $num = 1)
    {
        return $this->increment($column, -$num);
    }

    public function delete()
    {
        assert_exception(!empty($this->where), 'please set where when delete');
        list($where, $queryParams) = DB_Where::parse($this->where);
        $sql = sprintf('delete from %s where %s;', $this->mainTable, $where);
        Database::connection($this->connection)->execute($sql, $queryParams);
        return Database::connection($this->connection)->lastAffectRows();
    }

    public function struct()
    {
        $sql = 'SELECT COLUMN_NAME as field, DATA_TYPE as type, COLUMN_KEY as keyType, COLUMN_COMMENT as comment
        FROM  information_schema.COLUMNS 
        WHERE  TABLE_NAME =  \'%s\'
        AND TABLE_SCHEMA =  \'%s\'
        ORDER BY ORDINAL_POSITION';
        $database = Database::configValue($this->connection, 'database');
        $sql = sprintf($sql, $this->mainTable, $database);
        return Database::connection($this->connection)->fetchAll($sql, []);
    }
}