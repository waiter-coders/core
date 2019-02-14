<?php

namespace Waiterphp\Core\Console;


class Shell
{
    private static $yesOrNo = [
        'y'=>true,'yes'=>true,'n'=>false, 'no'=>false
    ];

    public static function getArg($key)
    {
        $arg = getopt($key.':');
        return isset($arg[$key]) ? $arg[$key] : false;
    }

    public static function isCmd()
    {
        return (PHP_SAPI == 'cli');
    }

    public static function output($message)
    {
        fwrite(STDOUT, $message . "\r\n");
    }

    public static function getInput($question)
    {
        self::output($question);
        return trim(fgets(STDIN));
    }

    public static function askUser($question)
    {
        $input = self::getInput($question . ' (y/n)');
        $input = strtolower($input);
        
        if (isset(self::$yesOrNo[$input])) {
            return self::$yesOrNo[$input];
        } else {
            self::output('输入错误');
            return self::askUser($question);
        }
    }
}
