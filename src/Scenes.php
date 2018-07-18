<?php
namespace Waiterphp\Core;

class Scenes
{
    public static function instance($name)
    {
        static $instances = array();
        if (!isset($instances[$name])) {
            $instances[$name] = new self();
        }
        return $instances[$name];
    }


    private $alias = array(); // 类别名
    private $values = array();
    private $config = array();
    private $object = array(); // 单例类
    private $events = array();

    public function setConfig($config)
    {
        $this->config = $config;
    }

    public function getConfig($docIndex)
    {
        return findDataByDot($docIndex, $this->config);
    }

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
            $result = call_user_func_array($action, $params);
            if (!$result) {
                break;
            }
        }
        return true;
    }

    // 单例工具
    public function object($class, $params = array(), $topic = 'default')
    {
        $class = (strpos($class, '.') > 0) ? dotToClass($class) : $class;
        if (isset($this->object[$class][$topic])) {
            return $this->object[$class][$topic];
        }
        $this->object[$class][$topic] = $this->factory($class, $params); // 生产对象
        return $this->object[$class][$topic];
    }

    public function factory($class, $params = array())
    {
        $class = (strpos($class, '.') > 0) ? dotToClass($class) : $class;
        return empty($params) ? new $class() : new $class($params);
    }
}