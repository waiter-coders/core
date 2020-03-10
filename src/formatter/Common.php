<?php
namespace waiterphp\core\formatter;

class Common
{
    public static function toInt($input)
    {
        return (int)preg_replace('/[^\d]/', '',  $input);
    }

    public static function toString($input)
    {
        // 只保留汉字、英文、数字、空格
        $output = preg_replace('/[^\u4e00-\u9fa5A-Za-z0-9 ]/', '', $input); 
        $output = preg_replace('/[ ]{2,}/', ' ', $output);
        return trim($output);
    }

    public static function toText()
    {
        // 只保留汉字、英文、数字、空格、回车
        $output = preg_replace('/[^\u4e00-\u9fa5A-Za-z0-9\n ]/', '', $input); 
        $output = preg_replace('/[ ]{2,}/', ' ', $output);
        return trim($output);
    }
}