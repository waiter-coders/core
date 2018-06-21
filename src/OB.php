<?php

namespace Waiterphp\Core;


class OB
{
    private static $isStart = false;

    public static function start()
    {
        ob_start();
        self::$isStart = true;
    }

    public static function endClean()
    {
        self::$isStart || self::start();

        ob_clean();
        ob_end_clean();
    }
}