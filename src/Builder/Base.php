<?php
namespace Waiterphp\Core\Builder;

use Waiterphp\Core\Builder\Maker as Maker;
use Waiterphp\Core\Builder\Dispatcher as Dispatcher;

abstract class Base
{
    abstract public function build($params = []);

    protected $basePath = '';

    public function __construct($basePath)
    {
        assert_exception(is_dir($basePath), 'base path is not exist:' . $basePath);
        $basePath = realpath($basePath);
        $this->basePath = $basePath;
    }

    protected function generateMaker()
    {
        return new Maker();
    }

    protected function dispatcher($package, $params = [], $basePath = '')
    {
        $basePath = empty($basePath) ? $this->basePath : $basePath;
        return (new Dispatcher($basePath))->build($package, $params);
    }
}