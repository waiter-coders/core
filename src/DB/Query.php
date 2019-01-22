<?php
namespace Waiterphp\Core\DB;

use Waiterphp\Core\Db\Parse\Where as DB_Where;
use Waiterphp\Core\Db\Parse\Join as DB_Join;

class Query
{
    public $connection = 'default';
    public $columns = '*';
    public $mainTable = null;
    public $join = '';
    public $where = array();
    public $groupBy = '';
    public $having ='';
    public $orderBy = '';
    public $limit = '0, 10000'; // 默认限制，最大一百条

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

    public function limit($limit, $offset = null)
    {
        $this->limit = empty($offset) ? $limit : $offset . ',' . $limit;
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
        $this->limit = '0,1';
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchRow($sql, $params);
    }

    public function fetchAll()
    {
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchAll($sql, $params);
    }

    public function fetchColumn()
    {
        $this->limit = '0,1';
        list($sql, $params) = $this->generateQuery();
        return Database::connection($this->connection)->fetchColumn($sql, $params);
    }

    public function fetchColumns($column)
    {
        $this->select($column);
        $list = $this->fetchAll();
        $return = array();
        foreach ($list as $record) {
            $return[] = $record[$column];
        }
        return $return;
    }

    public function count($column = '*')
    {
        $this->select('count('.$column.') as num');
        return $this->fetchColumn();
    }

    public function max($column)
    {
        $this->select('max('.$column.') as num');
        return $this->fetchColumn();
    }

    public function min($column)
    {
        $this->select('min('.$column.') as num');
        return $this->fetchColumn();
    }

    public function avg($column)
    {
        $this->select('avg('.$column.') as num');
        return $this->fetchColumn();
    }

    public function sum($column)
    {
        $this->select('sum('.$column.') as num');
        return $this->fetchColumn();
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
        $sql .= ' limit ' . $this->limit;
        return array($sql, $queryParams);
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
        list($updateSql, $updateParams) = $this->parseUpdateData($data);
        $sql = 'update ' . $this->mainTable . ' set ' . $updateSql . ' where ' . $where;
        $params = array_merge($updateParams, $queryParams);
        Database::connection($this->connection)->execute($sql, $params);
        return Database::connection($this->connection)->lastAffectRows();
    }

    public function increment($column, $num = 1)
    {
        return $this->update(array(
            $column . ' = ' . $column . ' + ' . $num
        ));
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


    private function parseUpdateData($data)
    {
        $sql = array();
        $params = array();
        foreach ($data as $key => $value) {
            if (is_int($key)) {
                $sql[] = $value;
            } else {
                $sql[] = $key . '=?';
                $params[] = $value;
            }
        }
        return array(implode(',', $sql), $params);
    }
}