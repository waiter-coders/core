<?php
namespace waiterphp\core\database\query\parse;
use waiterphp\core\database\Query as Query;

/*
 * Join链接对象，用于提供Join方法
 */
class Join
{
    private $query;
    private $joinTable;
    private $joinType;
    public function __construct(Query $query, $joinTable, $joinType)
    {
        $this->query = $query;
        $this->joinTable = $joinTable;
        $this->joinType = $joinType;
    }

    public function on($on)
    {
        $this->query->join = sprintf(' %s %s join %s on %s', $this->query->join, $this->joinType, $this->joinTable, $on);
        return $this->query;
    }

    public function using($column)
    {
        $this->query->join = sprintf(' %s %s join %s using(%s)', $this->query->join, $this->joinType, $this->joinTable, $column);
        return $this->query;
    }
}