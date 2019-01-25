<?php
namespace Waiterphp\Core\DB\Connection;
abstract class Connection
{
    abstract public function __construct($connectConfig);
    abstract public function execute($sql, $params = []);
    abstract public function fetchRow($sql, $params = []);
    abstract public function fetchAll($sql, $params = []);
    abstract public function fetchColumn($sql, $params = []);
    abstract public function lastAffectRows();
    abstract public function lastInsertId();
}