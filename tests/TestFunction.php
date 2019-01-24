<?php

namespace Waiterphp\Core\Tests;

class TestFunction extends TestCase
{
    public function test_load_configs()
    {
        $configs = load_configs(['web.php'], [__DIR__ . '/config']);
        $this->assertTrue(isset($configs['database']));
    }

    public function test_set_get_env()
    {
        set_env('app_name', 'test'); 
        $app_name = get_env('app_name');
        $this->assertEquals($app_name, 'test');
    }

    public function test_assert_exception()
    {
        try {
            assert_exception(false, 'test error', 502);
        } catch (\Exception $e) {
            $this->assertEquals($e->getMessage(), 'test error');
            $this->assertEquals($e->getCode(), 502);
        }
    }
}