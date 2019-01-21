<?php
namespace Waiterphp\Core\Base;

class Environment
{
    public static function instance()
    {
        static $instance = null;
        if (empty($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    private $values = array();
    private $events = array();

    public function set($key, $value)
    {
        $this->values[$key] = $value;
    }

    public function get($docIndex)
    {
        return isset($this->values[$docIndex]) ? $this->values[$docIndex] : null;
    }

    public function bind($tab, $action)
    {
        $this->events[$tab][] = $action;
    }

    public function trigger($tab, $params = array())
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
