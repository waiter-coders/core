<?php
namespace Waiterphp\Core\Env;

use Waiterphp\Core\Dot\Dot as Dot;

class Context
{
    public static function instance()
    {
        static $instance = null;
        if (empty($instance)) {
            $instance = new self();
        }
        return $instance;
    }

    private $values = [];
    private $events = [];

    public function set($key, $value)
    {
        $setArray = [];
        $dotKeys = array_reverse(explode('.', $key));
        $firstKey = array_shift($dotKeys);
        $setArray[$firstKey] = $value;
        foreach ($dotKeys as $dotKey) {
            $setArray = [$dotKey=>$setArray];
        }
        $this->values = array_deep_cover($this->values, $setArray);
    }

    public function get($docIndex)
    {
        return Dot::findDataByDot($docIndex, $this->values);
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
