<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/19
 * Time: 16:40
 */

namespace Waiterphp\Core\Filter;

use Waiterphp\Core\Filter\FilterTrait as FilterTrait;

class Filter
{
    use FilterTrait;

    public static function instance($data)
    {
        static $instance = null;
        if ($instance == null) {
            $instance = new self($data);
        }
        return $instance;
    }

    private function __construct($data)
    {
        $this->setData($data);
    }
}