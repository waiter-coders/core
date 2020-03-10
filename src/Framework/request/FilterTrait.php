<?php
namespace waiterphp\core\Filter;
trait FilterTrait
{
    private $data = [];

    protected function setData($data)
    {
        $this->data = $data;
    }

    public function isHtml($page)
    {
        return true;
    }

    public function getInt($key, $default = null)
    {
        $result = isset($this->data[$key]) ? $this->data[$key] : $default;
        return empty($result) ? $default : (int)$result;
    }

    public function getArray($key, $default = null)
    {
        return isset($this->data[$key]) ? is_string($this->data[$key]) ? json_decode($this->data[$key], true) : $this->data[$key] : $default;
    }

    public function getString($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function getText($key, $default = null)
    {
        $text = isset($this->data[$key]) ? $this->data[$key] : $default;
        // ----- remove HTML TAGs -----
        $text = preg_replace ('/<[^>]*>/', ' ', $text);
        $text = str_replace('&nbsp;', '', $text);

        // ----- remove control characters -----
        $text = str_replace("\r", '', $text);    // --- replace with empty space
        $text = str_replace("\n", ' ', $text);   // --- replace with space
        $text = str_replace("\t", ' ', $text);   // --- replace with space

        // ----- remove multiple spaces -----
        $text = trim(preg_replace('/ {2,}/', ' ', $text));

        return $text;
    }

    public function getHtml($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    public function getEmail($key, $default = null)
    {
        $result = isset($this->data[$key]) ? $this->data[$key] : $default;
        return empty($result) ? $default : filter_var($result, FILTER_VALIDATE_EMAIL);
    }

    public function getBoolean($key, $default = null)
    {
        $result = isset($this->data[$key]) ? $this->data[$key] : $default;
        return empty($result) ? false : true;
    }

    public static function datetime($datetime, $format = 'Y-m-d H:i:s')
    {

    }

    public static function select($select, $map)
    {

    }

    public static function path($path)
    {

    }

    public static function url($url)
    {

    }

    public static function email($email)
    {

    }

    public static function phone($phone)
    {

    }
}