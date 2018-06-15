<?php
namespace Waiterphp\Core;

class Env
{
    public static function instance($envName = 'default')
    {
        static $instances = array();
        if (!isset($instances[$envName])) {
            $instances[$envName] = new self();
        }
        return $instances[$envName];
    }

    private $config = array();
    private $action = array();

    public function set($env)
    {
        $this->config = $env;
    }

    public function get($docIndex)
    {
        return findDataByDot($docIndex, $this->config);
    }

    public function bind($tab, $action)
    {
        $this->action[$tab][] = $action;
    }

    public function trigger($tab, $params = array())
    {
        if (!isset($this->action[$tab])) {
            return false;
        }
        foreach ($this->action[$tab] as $action) {
            $result = call_user_func_array($action, $params);
            if (!$result) {
                break;
            }
        }
        return true;
    }


}