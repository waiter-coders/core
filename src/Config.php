<?php
namespace Waiterphp\Core;

class Config
{
    public static function loadFiles($fileNames, $basePaths)
    {
        $config = array();
        $fileNames = is_string($fileNames) ? array($fileNames) : $fileNames;
        $basePaths = is_string($basePaths) ? array($basePaths) : $basePaths;
        foreach ($basePaths as $basePath) {
            foreach ($fileNames as $fileName) {
                $filePath = $basePath . DIRECTORY_SEPARATOR . $fileName;
                if (is_file($filePath)) {
                    $config = self::merge($config, require $filePath);
                }
            }
        }
        return $config;
    }

    public static function merge($config, $targetConfig)
    {
        $targetConfig = !is_array($targetConfig) ? array() : $targetConfig;
        return array_deep_cover($config, $targetConfig);
    }
}