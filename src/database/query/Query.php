<?php
namespace waiterphp\core\database\Query;
use waiterphp\core\database\query\parse\Where;
use waiterphp\core\database\query\parse\Join;
use waiterphp\core\database\query\parse\Update;
use waiterphp\core\database\connection\Selector;

/**
 * 查询构造器，用于构造一条sql语句
 */
class Query
{
    // 基础信息
    private $name = null;
    private $table = null;
    
    // sql相关
    private $columns = '*';
    private $join = '';
    private $where = [];
    private $groupBy = '';
    private $having ='';
    private $orderBy = '';
    private $limit = 10;
    private $offset = 0;
    private $database = '';

    // sql记录
    private $sql = '';
    private $sqlParams = [];

    public function __construct($table, $name)
    {
        $this->table = $table;
        $this->name = $name;
    }

    /*
     * 连续操作的相关方法
     */

    // 设置查询字段
    public function select($columns)
    {
        $this->columns = is_array($columns) ? implode(',', $columns) : $columns;
        return $this;
    }

    // 设置条件
    public function where($where)
    {
        $this->where = $where;
        return $this;
    }

    // 设置排序
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    // 设置查询数量
    public function limit($limit)
    {
        $this->limit = (int)$limit;
        return $this;
    }

    // 设置查询offset
    public function offset($offset)
    {
        $this->offset = (int)$offset;
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

    public function database($database)
    {
        $this->database = $database;
        return $this;
    }

    /*
     * 获取数据的相关方法
     */
    public function fetchAll()
    {
        list($sql, $params) = $this->makeSelectSql();
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $data = $this->connection('read')->execute($sql, $params, 'fetchAll');
        $this->clearState();
        return $data;
    }

    public function fetchRow()
    {
        $this->limit = 1;
        list($sql, $params) = $this->makeSelectSql();
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $data = $this->connection('read')->execute($sql, $params, 'fetch');
        $this->clearState();
        return $data;
    }

    
    public function fetchColumn($column)
    {
        $this->limit = 1;
        $this->columns = $column;
        list($sql, $params) = $this->makeSelectSql();
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $data = $this->connection('read')->execute($sql, $params, 'fetchColumn');
        $this->clearState();
        return $data;
    }

    public function fetchColumns($column)
    {
        $this->columns = $column;
        $list = $this->fetchAll();
        $list = array_map(function($row) use ($column){
            return $row[$column];
        }, $list);
        return $list;
    }    

    /**
     * 统计方法
     */

    public function count($column = '*')
    {
		$this->orderBy = '';
        return (int) $this->fetchColumn('count('.$column.') as num');
    }

    public function max($column)
    {
		$this->orderBy = '';
        return $this->fetchColumn('max('.$column.') as num');
    }

    public function min($column)
    {
		$this->orderBy = '';
        return $this->fetchColumn('min('.$column.') as num');
    }

    public function avg($column)
    {
		$this->orderBy = '';
        return $this->fetchColumn('avg('.$column.') as num');
    }

    public function sum($column)
    {
		$this->orderBy = '';
        return $this->fetchColumn('sum('.$column.') as num');
    }

    /*
     * join连接的相关方法
     */
    public function leftJoin($table)
    {
        return new Join($this, $table, 'left');
    }

    public function rightJoin($table)
    {
        return new Join($this, $table, 'right');
    }

    public function innerJoin($table)
    {
        return new Join($this, $table, 'inner');
    }

    public function fullJoin($table)
    {
        return new Join($this, $table, 'full');
    }

    private function makeSelectSql()
    {
        // 基础sql
        $sql = sprintf('select %s from %s', $this->columns, $this->table);
        // 连接表
        if ($this->join != '') {
            $sql .= ' ' . $this->join;
        }
        // 条件
        list($where, $params) = Where::parse($this->where);
        if (!empty($where)) {
            $sql .= sprintf(' where %s', $where);
        }
        // 排序
        if (!empty($this->orderBy)) {
            $sql .= sprintf(' order by %s', $this->orderBy);
        }
        // 分组
        if (!empty($this->groupBy)) {
            $sql .= sprintf(' group by %s', $this->groupBy);
            if (!empty($this->having)) {
                $sql .= sprintf(' having %s', $this->having);
            }
        }
        // 条数限制
        $sql .= sprintf(' limit %d, %d;', $this->offset, $this->limit);
        return [$sql, $params];
    }

    /*
     * 操作数据的相关方法
     */
    // 插入数据
    public function insert($data)
    {
        $columns = implode(',', array_keys($data));
        $values = implode(',', array_fill(0, count($data), '?'));
        $sql = sprintf('insert into %s (%s) values (%s)', $this->table, $columns, $values);
        $params = array_values($data);
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $connection = $this->connection('write');
        $insertId = $connection->execute($sql, $params, 'lastInsertId');
        $this->clearState();
        return $insertId;
    }

    // 更新数据
    public function update($data)
    {
        assert_exception(!empty($this->where), 'please set where when update');
        list($where, $params) = Where::parse($this->where);
        list($updateSql, $updateParams) = Update::formatData($data);
        $sql = sprintf('update %s set %s where %s', $this->table, $updateSql, $where);
        $params = array_merge($updateParams, $params);
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $connection = $this->connection('write');
        $rowCount = $connection->execute($sql, $params, 'rowCount');
        $this->clearState();
        return $rowCount;
    }

    // 递增数据
    public function increment($column, $num = 1)
    {
        $update = sprintf('%s = %s + %d', $column, $column, $num);
        return $this->update([$update]);
    }

    // 递减数据
    public function decrement($column, $num = 1)
    {
        return $this->increment($column, -$num);
    }

    // 删除数据
    public function delete()
    {
        assert_exception(!empty($this->where), 'please set where when delete');
        list($where, $params) = Where::parse($this->where);
        $sql = sprintf('delete from %s where %s;', $this->table, $where);
        list($this->sql, $this->sqlParams) = [$sql, $params];
        $connection = $this->connection('write');
        $rowCount = $connection->execute($sql, $params, 'rowCount');
        $this->clearState();
        return $rowCount;
    }

    private function connection($database)
    {
        $database = $this->database != '' ? $this->database : $database;
        return Selector::select($this->name, $database);
    }

    // 输出sql
    public function sql()
    {
        // $sql = $this->sql;
        // $params = $this->sqlParams; TODO
        // if ($sql == '') {
        //     list($sql, $params) = $this->makeSelectSql();
        // }
        $result = '';
        list($sql, $params) = $this->makeSelectSql();
        $sql = explode('?', $sql);
        foreach ($sql as $key =>$value) {
            $result .= $value;
            if (isset($params[$key])) {
                $result .= $params[$key];
            }
        }
        return $result;
    }

    private function clearState()
    {
        $this->columns = '*';
        $this->join = '';
        $this->where = [];
        $this->groupBy = '';
        $this->having ='';
        $this->orderBy = '';
        $this->limit = 10;
        $this->offset = 0;
        $this->database = '';
    }
}