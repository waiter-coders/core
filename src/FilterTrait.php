<?php
namespace Waiterphp\Core;
trait FilterTrait
{
    private $filterData = array();

    protected function setFilterData($data)
    {
        $this->filterData = $data;
    }

    public function isHtml($page)
    {
        return true;
    }

    public function getInt($key, $default = null)
    {
        $result = isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
        return empty($result) ? $default : (int)$result;
    }

    public function getArray($key, $default = null)
    {
        return isset($this->filterData[$key]) ? is_string($this->filterData[$key]) ? json_decode($this->filterData[$key], true) : $this->filterData[$key] : $default;
    }

    public function getString($key, $default = null)
    {
        return isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
    }

    public function getText($key, $default = null)
    {
        return isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
    }

    public function getHtml($key, $default = null)
    {
        return isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
    }

    public function getEmail($key, $default = null)
    {
        $result = isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
        return empty($result) ? $default : filter_var($result, FILTER_VALIDATE_EMAIL);
    }

    public function getBoolean($key, $default = null)
    {
        $result = isset($this->filterData[$key]) ? $this->filterData[$key] : $default;
        return empty($result) ? false : true;
    }
}