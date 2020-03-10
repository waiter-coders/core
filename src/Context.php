<?php
namespace waiterphp\core;

use waiterphp\core\database\Database;

class Context
{
    private $configs = [];
    private $events = [];

    // 初始化配置
    public function init($configs)
    {
        $this->configs = $configs;   
        if (isset($this->configs['database'])) {
            Database::register($this->configs['database']);
        }
    }

    public function set($key, $value)
    {
        $set = [];
        $dotKeys = array_reverse(explode('.', $key));
        $firstKey = array_shift($dotKeys);
        $set[$firstKey] = $value;
        foreach ($dotKeys as $dotKey) {
            $set = [$dotKey=>$set];
        }
        $this->configs = array_deep_merge($this->configs, $set);
    }

    public function get($dotKey)
    {
        $dot = explode('.', $dotKey);
        $data = $this->configs;        
        foreach ($dot as $key) {
            assert_exception(isset($data[$key]), 'has no item:' . $key);
            $data = $data[$key];
        }
        return $data;
    }

    public function bind($tab, $action)
    {
        $this->events[$tab][] = $action;
    }

    public function trigger($tab, $params = [])
    {
        if (!isset($this->events[$tab])) {
            return false;
        }
        foreach ($this->events[$tab] as $action) {
            if (Factory::action($action, $params) == false) {
                break;
            }
        }
        return true;
    }
}
