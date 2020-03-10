<?php
// declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('UTC');
$context = context();
$configs = load_configs(__DIR__.'/config/config.php');
$context->init($configs);
