<?php
namespace Waiterphp\Core\Lib;
class Router
{
    private $routeTable = array();

    public function group()
    {
        return $this;
    }

    public function set($routeTable)
    {
        $this->routeTable = $routeTable;
    }

    public function route($signal, $params = array())
    {
        $routeTarget = $this->target($signal);
        return $this->routeTo($routeTarget, $params);
    }

    public function target($signal)
    {
        $signal = $this->parseSignal($signal);
        return $this->searchTarget($this->routeTable, $signal);
    }

    private function searchTarget($routes, $signal)
    {
        foreach ($routes as $route) {
            assertOrException(isset($route[0]) || isset($route['url']), 'route not set');
            $pattern = isset($route[0]) ? $route[0] : $route['url'];
            if (preg_match($this->formatPattern($pattern), $signal, $matches)) {
                return $this->generateCmd($route[1], $matches);
            }
        }
        return false;
    }

    private function formatPattern($pattern)
    {
        return "/" . str_replace('/', '\/', $pattern) . '/i';
    }

    private function generateCmd($action, $matches)
    {
        foreach ($matches as $key=>$match) {
            if ($key > 0) {
                $action = str_replace('$'.$key, $match, $action);
            }
        }
        return $action;
    }

    private function routeTo($action, $params = array())
    {
        return method($action, $params);
    }

    private function parseSignal($signal)
    {
        if (is_callable($signal)) {
            return $signal();
        }
        return $signal;
    }
}