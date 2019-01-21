<?php

class PdoDatabaseInstance extends DatabaseInstance
{
    private $config = array();
    private $connectionPool = array();
    private $lastSql = '';
    private $lastParams = array();
    private $lastInsertId = 0;
    private $lastAffectRows = 0;
    public $useWriteServers = false;
    public $hasReadonlyServers = false;

    public function __construct($config)
    {
        $this->config = $config;
        if (!empty($config['read'])) {
            $this->hasReadonlyServers = true;
        }
    }

    public function beginTransaction()
    {
        $this->onlyUseWriteServers();
        $this->connection('write')->beginTransaction();
    }

    public function commit()
    {
        $this->connection('write')->commit();
        $this->cancelForceWriteServers();
    }

    public function rollBack()
    {
        $this->connection('write')->rollBack();
        $this->cancelForceWriteServers();
    }

    public function execute($sql, $params = array())
    {
        try {
            $this->resetQueryStatus($sql, $params);
            $connection = $this->connection('write');
            $statement = $connection->prepare($sql);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute($params);
            $this->lastInsertId = $connection->lastInsertId();
            $this->lastAffectRows = $statement->rowCount();
        }catch(\PDOException $e){
            throw new \Exception('sql error:' . $this->lastSql . PHP_EOL . json_encode($this->lastParams));
        }
    }

    public function fetchRow($sql, $params = array())
    {
        return $this->fetchData($sql, $params, 'fetch');
    }

    public function fetchAll($sql, $params = array())
    {
        return $this->fetchData($sql, $params, 'fetchAll');
    }

    public function fetchColumn($sql, $params = array())
    {
        return $this->fetchData($sql, $params, 'fetchColumn');
    }

    public function lastAffectRows()
    {
        return $this->lastAffectRows;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    public function lastSql()
    {
        return array($this->lastSql, $this->lastParams);
    }

    public function onlyUseWriteServers()
    {
        $this->useWriteServers = true;
    }

    public function cancelForceWriteServers()
    {
        $this->useWriteServers = false;
    }

    private function fetchData($sql, $params = array(), $fetchType)
    {
        try {
            $this->resetQueryStatus($sql, $params);
            $connectType = $this->useWriteServers ? 'write' : 'read';
            $statement = $this->connection($connectType)->prepare($sql);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute($params);
            return call_user_func(array($statement, $fetchType));
        }catch(\PDOException $e){
            throw new \Exception('sql error:' . $this->lastSql . PHP_EOL . json_encode($this->lastParams));
        }
    }

    private function resetQueryStatus($sql, $params)
    {
        $this->lastSql = $sql;
        $this->lastParams = $params;
        $this->lastInsertId = 0;
        $this->lastAffectRows = 0;
    }

    private function connection($connectType = 'write')
    {
        // 不存在read服务器则全部使用write服务器
        if ($connectType == 'read' && $this->hasReadonlyServers == false) {
            $connectType = 'write';
        }
        if (!isset($this->connectionPool[$connectType])) {
            $config = $this->selectConnectConfig($connectType);
            $dsn = sprintf('%s:host=%s;dbname=%s;port=%s;',$config['driver'], $config['host'], $config['database'], $config['port']);
            $connection = @new \PDO($dsn, $config['username'], $config['password']);
            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $connection->exec("SET NAMES '{$config['charset']}'");
            $this->connectionPool[$connectType] = $connection;
        }
        return $this->connectionPool[$connectType];
    }

    private function selectConnectConfig($server)
    {
        $config = $this->config[$server];
        $randIndex = mt_rand(0, count($config) - 1);
        return $config[$randIndex];
    }
}