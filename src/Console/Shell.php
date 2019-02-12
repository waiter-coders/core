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

    public static function getInput($message)
    {
        echo $message . "\r\n";
        $input = trim(fgets(STDIN));
        if ($input == 'exit') {
            exit();
        }
        return $input;
    }

    public static function askUser($question)
    {
        $input = self::getInput($question . ' (y/n)');
        $input = strtolower($input);
        
        if (isset(self::$yesOrNo[$input])) {
            return self::$yesOrNo[$input];
        } else {
            echo '输入错误' . "\r\n";
            return $this->askUser($question);
        }
    }
}
