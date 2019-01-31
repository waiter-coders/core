<?php

namespace Waiterphp\Core\Builder;

class Maker
{
    private $content = '';


    public function compile($template, $params)
    {
        $content = file_get_contents($template);
        return $this;
    }

    public function buildToFile($file)
    {

    }

    public function getContent()
    {
        return $this->content;
    }
} 