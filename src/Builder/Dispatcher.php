<?php

namespace Waiterphp\Builder;

use Waiterphp\Core\Env\Factory as Factory;
use Waiterphp\Builder\Base as BuilderBase;

class Dispatcher
{
    private $basePath = '';

    private $systemBuilder = [];

    public function __construct($basePath)
    {
        assert_exception(is_dir($basePath), 'base path is not exist:' . $basePath);
        $basePath = realpath($basePath);
        $this->basePath = $basePath;
        $this->systemBuilder = load_configs(__DIR__ . '/config/builder.php', false);
    }

    public function setBuilderRelative($relative)
    {
        $this->systemBuilder = array_merge($this->systemBuilder, $relative);
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
        $object = factory($package, $this->basePath);
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
        assert_exception(isset($this->systemBuilder[$package]), $this->actionError($package));
        return $this->systemBuilder[$package];
    }

    private function actionError($package)
    {
        return sprintf('not has action %s,user package must use setBuilderRelative to set', $package);
    }
} 