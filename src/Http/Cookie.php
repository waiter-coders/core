<?php

namespace Waiterphp\Core\Http;

class Cookie
{
    public static function set($name, $value, $expire = 300, 
                                $path = '', $domain = '', $secure = false, $httpOnly = false)
    {
        $value = $secure ? self::encrypt($value) : $value;
        return setcookie($name, $value, $expire, $path, $domain);
    }

    public static function get($name, $isDecrypt = true)
    {
        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : '';
        $value = $isDecrypt ? self::decrypt($value) : $value;
        return $value;
    }

    public static function delete($name)
    {
        self::set($name, '', time() - 3600);
    }

    private static function encrypt($value)
    {
        return $value;
    }

    private static function decrypt($value)
    {
        return $value;
    }

}