<?php

namespace Waiterphp\Core\Builder;

use Waiterphp\Core\Env\Factory as Factory;
use Waiterphp\Core\Builder\Base as BuilderBase;

class Dispatcher
{
    private $basePath = '';

    private $systemBuilder = [
        'dao'=>'waiterphp.core.builder.main.dao',
    ];

    public function __construct($basePath)
    {
        assert_exception(is_dir($basePath), 'base path is not exist:' . $basePath);
        $basePath = realpath($basePath);
        $this->basePath = $basePath;
    }

    public function build($package, $params = [])
    {
        $package = $this->truePackage($package);
        $builder = $this->makeBuilder($package);
        return call_user_func_array([$builder, 'build'], [$params]);
    }

    private function makeBuilder($package)
    {
        if (Factory::hasClass($package) == false) {
            $this->installPackage($package);
        }        
        $object = factory($package, [$this->basePath]);
        assert_exception($object instanceof BuilderBase, 'package not is builder base:' . $package);return $object; 
    }


    private function installPackage($package)
    {
        print 'install package start:' . $package;

        print 'install ok!';
    }

    // 获取真实的包名
    private function truePackage($package)
    {
        if (isset($this->systemBuilder[$package])) {
            return $this->systemBuilder[$package];
        }
        return $package;
    }
} 