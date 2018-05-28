<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/28
 * Time: 14:36
 */

namespace Waiterphp\Core;


class Cookie
{
    public static function get($key)
    {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : '';
    }

    public static function set($key, $value, $expire = 300)
    {
        setcookie($key, $value, $expire);
    }

    public static function safeSet($key, $value, $expire = 300)
    {
        $value = self::encrypt($value);
        self::set($key, $value, $expire);
    }

    public static function safeGet($key, $value, $expire = 300)
    {
        $value = self::get($key);
        return self::decrypt($value);
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