<?php
require __DIR__.'/../vendor/autoload.php';
date_default_timezone_set('UTC');
set_env(load_configs(['web.php'], [__DIR__ . '/config', __DIR__ . '/../../config']));