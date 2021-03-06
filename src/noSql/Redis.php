<?php
namespace waiterphp\core\Cache;

class Redis
{
    static private $slaveMethod = [
        'get' => '',
        'exists' => '',
        'getMultiple' => '',
        'lSize' => '',
        'lIndex' => '',
        'lGet' => '',
        'lRange' => '',
        'lGetRange' => '',
        'sIsMember' => '',
        'sContains' => '',
        'sCard' => '',
        'sSize' => '',
        'sRandMember' => '',
        'sInter' => '',
        'sInterStore' => '',
        'sUnion' => '',
        'sUnionStore' => '',
        'sDiff' => '',
        'sDiffStore' => '',
        'sMembers' => '',
        'sGetMembers' => '',
        'randomKey' => '',
        'keys' => '',
        'getKeys' => '',
        'dbSize' => '',
        'type' => '',
        'getRange' => '',
        'strlen' => '',
        'getBit' => '',
        'info' => '',
        'ttl' => '',
        'zRange' => '',
        'zRevRange' => '',
        'zRangeByScore' => '',
        'zRevRangeByScore' => '',
        'zCount' => '',
        'zSize' => '',
        'zCard' => '',
        'zScore' => '',
        'zRank' => '',
        'zRevRank' => '',
        'zUnion' => '',
        'zInter' => '',
        'hGet' => '',
        'hLen' => '',
        'hDel' => '',
        'hKeys' => '',
        'hVals' => '',
        'hGetAll' => '',
        'hExists' => '',
        'hMGet' => ''
    ];
    private $config = [];
    private $redisPool = [];
    private $connectTimeOut = 4;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function __call($method, $params)
    {
        if (isset(self::$slaveMethod[$method])) {
            $redis = $this->getRedis('slave');
        } else {
            $redis = $this->getRedis('master');
        }

        return call_user_func_array([$redis, $method], $params);
    }

    public function getRedis($type)
    {
        if (empty($this->redisPool[$type])) {
            if (!isset($this->config[$type])) {
                throw new \Exception('redis config not has:' . $type);
            }
            $this->redisPool[$type] = $this->newRedis($this->config[$type]);
        }
        return $this->redisPool[$type];
    }

    private function newRedis($config)
    {
        assert_exception(isset($config['host']) && isset($config['port']), 'redis config error');
        $redis = new \Redis();
        $redis->connect($config['host'], $config['port'], $this->connectTimeOut);
        if (isset($config['pass'])) {
            $redis->auth($config['pass']);
        }

        return $redis;
    }

    public function __destruct()
    {
        foreach ($this->redisPool as $link) {
            if (method_exists($link, 'close')) {
                $link->close();
            }
        }
    }
}