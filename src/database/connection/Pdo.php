<?php
namespace waiterphp\core\database\connection;

/**
 * 关系数据库连接类
 */
class Pdo
{
    // 基本信息
    private $config = [];
    private $connection = '';
    
    // query状态
    private $sql = '';
    private $params = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function execute($sql, $params = [], $fetchType = 'fetchAll')
    {
        $this->saveSql($sql, $params); 
        $connection = $this->connection();
        try {                       
            $statement = $connection->prepare($sql);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute($params);
            if ($fetchType == 'fetchAll' || $fetchType == 'fetch' || $fetchType == 'fetchColumn') {
                return call_user_func([$statement, $fetchType]);
            } else if ($fetchType == 'rowCount') {
                return $statement->rowCount();
            } else if ($fetchType == 'lastInsertId') {
                return $connection->lastInsertId();
            }
        }catch(\PDOException $e){
            throw new \Exception('sql error:' . $this->sql . PHP_EOL . json_encode($this->params));
        }
    }  

    public function beginTransaction()
    {
        $this->connection()->beginTransaction();
    }

    public function commit()
    {
        $this->connection()->commit();
    }

    public function rollBack()
    {
        $this->connection()->rollBack();
    }

    /**
     * 基础功能
     */   
    public function sql()
    {
        return [$this->sql, $this->params];
    }

    private function saveSql($sql, $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    private function connection()
    {
        if ($this->connection === '') {
            $this->connection = new \PDO(
                $this->config['dsn'],
                $this->config['option']['username'],
                $this->config['option']['password']
            );
            $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->connection->exec("SET NAMES '" .
                 $this->config['option']['charset'] . "'");
        }
        return $this->connection;
    }

    
}