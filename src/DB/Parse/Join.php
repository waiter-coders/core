<?php
/*
 * Join链接对象，用于提供Join方法
 */
class DB_Join
{
    private $query;
    private $joinTable;
    private $joinType;
    public function __construct(DB_Query $query, $joinTable, $joinType)
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