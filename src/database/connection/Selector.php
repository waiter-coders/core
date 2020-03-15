<?php
namespace waiterphp\core\database\connection;

class Selector
{    
    private static $configs = [];
    private static $defaultName = '';
    private static $pool = [];

    public static function register($configs)
    {
        assert_exception(count($configs) > 0, 'database config is empty');
        foreach ($configs as $name=>$config) {            
            self::$configs[$name] = self::covertConfig($config);
        }
        self::$defaultName = array_keys($configs)[0];
    }

    public static function select($name = '', $feature = 'write')
    {
        $name = ($name === '') ? self::$defaultName : $name;
        assert_exception(isset(self::$configs[$name]), 'name not set:' . $name);
        $config = self::$configs[$name];
        if (isset($config[$feature])) {
            $config = $config[$feature];
        }
        if (isset($config[0])) {
            $config = self::randSelect($config);
        }
        return self::connection($config);
    }    

    private static function connection($config)
    {
        if (!isset(self::$pool[$config['dsn']])) {
            self::$pool[$config['dsn']] = self::factory($config);
        }
        return self::$pool[$config['dsn']];
    }

    private static function factory($dns)
    {
        return new Pdo($dns);
    }

    private static function covertConfig($configs)
    {
        // 主从配置        
        if (isset($configs['read'])) {
            $result = [];
            foreach ($configs as $type=>$config) {
                if ($type !== 'read' || $type != 'write') {                    
                    continue;
                }
                $result[$type] = self::covertConfig($config);
            }
            return $result;
        }
        // 多库配置
        else if (isset($config[0])) {
            $result = [];
            foreach ($configs as $config) {
                $result[] = self::covertConfig($config);
            }
            return $result;
        }
        // 单库配置
        else {
            assert_exception(isset($configs['database']), 'not set database');
            $driver = isset($configs['driver']) ? $configs['driver'] : 'mysql';
            $host = isset($configs['host']) ? $configs['host'] : '127.0.0.1';
            $port = isset($configs['port']) ? $configs['port'] : 3306;        
            $dsn = sprintf('%s:host=%s;dbname=%s;port=%s;',$driver, $host, $configs['database'], $port);
            $username = isset($configs['username']) ? $configs['username'] : 'root';
            $password = isset($configs['password']) ? $configs['password'] : '';
            $charset = isset($configs['charset']) ? $configs['host'] : 'utf8';
            return [
                'dsn'=>$dsn,
                'option'=>[
                    'username'=>$username,
                    'password'=>$password,
                    'charset'=>$charset
                ]
            ];
        }       
    }

    private function randSelect($configs)
    {
        $index = mt_rand(0, count($configs) - 1);
        return $configs[$index];
    }
}