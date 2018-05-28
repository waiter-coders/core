<?php
namespace Waiterphp\Core;

class Env
{

    public static function get($envName = 'default')
    {
        return Container::instance('Env', array(), $envName);
    }

    public function loadEnvFile($file, $path, $upNum = 0)
    {
        $env = array();
        $searchPath = $path;
        do {
            assertOrException(is_dir($searchPath), 'search path not exist');
            $searchFile = $searchPath . DIRECTORY_SEPARATOR . $file;
            if (file_exists($searchFile)) {
                $env = require $searchFile;
                assertOrException(is_array($env), 'file config error' . $searchFile);
                break;
            }
            $searchPath = dirname($searchPath);
            $upNum--;
        } while ($upNum > 0);
        return $env;
    }

    public function checkAndRegister($env, $checkKeys = array())
    {

    }
}