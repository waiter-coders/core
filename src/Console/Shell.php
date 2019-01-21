<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/11
 * Time: 14:35
 */

namespace Waiterphp\Core\Console;


class Shell
{
    public static function getArg($key)
    {
        $arg = getopt($key.':');
        return isset($arg[$key]) ? $arg[$key] : false;
    }

    public static function isCmd()
    {
        return (PHP_SAPI == 'cli');
    }

    public static function getInput($message)
    {
        echo $message . "\r\n";
        $input = trim(fgets(STDIN));
        if ($input == 'exit') {
            exit();
        }
        return $input;
    }
}
