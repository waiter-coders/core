<?php

abstract class DatabaseInstance
{
    abstract public function __construct($connectConfig);
    abstract public function execute($sql, $params = array());
    abstract public function fetchRow($sql, $params = array());
    abstract public function fetchAll($sql, $params = array());
    abstract public function fetchColumn($sql, $params = array());
    abstract public function lastAffectRows();
    abstract public function lastInsertId();
}