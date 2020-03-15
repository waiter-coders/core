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
    
    // 查询状态
    private $sql = '';
    private $params = [];
    private $lastInsertId = 0;
    private $rowCount = 0;

    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * 查询
     */
    public function fetchAll($sql, $params = [])
    {
        return $this->fetchData($sql, $params, 'fetchAll');
    }

    public function fetchRow($sql, $params = [])
    {
        return $this->fetchData($sql, $params, 'fetch');
    }    

    public function fetchColumn($sql, $params = [])
    {
        return $this->fetchData($sql, $params, 'fetchColumn');
    }

    private function fetchData($sql, $params = [], $fetchType)
    {
        $this->saveSql($sql, $params);
        $connection = $this->connection();
        try {            
            $statement = $connection->prepare($sql);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute($params);
            return call_user_func([$statement, $fetchType]);
        }catch(\PDOException $e){
            throw new \Exception('sql error:' . $this->sql . PHP_EOL . json_encode($this->params));
        }
    }

    /**
     * 操作相关
     */
    public function execute($sql, $params = [])
    {
        $this->saveSql($sql, $params); 
        $connection = $this->connection();
        try {                       
            $statement = $connection->prepare($sql);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute($params);
            $this->lastInsertId = $connection->lastInsertId();
            $this->rowCount = $statement->rowCount();
        }catch(\PDOException $e){
            throw new \Exception('sql error:' . $this->sql . PHP_EOL . json_encode($this->params));
        }
    }  

    public function rowCount()
    {
        return $this->rowCount;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * 事务
     */

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