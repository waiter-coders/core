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
        // 设置环境变量
        set_env('app_name', 'test_name'); 
        $app_name = get_env('app_name');
        $this->assertEquals($app_name, 'test_name');

        // dot设置
        set_env('database.default.username', 'dot_tests');
        $this->assertEquals(get_env('database.default.username'), 'dot_tests');

        // 直接数组写入，后者覆盖前者
        set_env(['database'=>['default'=>[
            'host'=>'localhost',
            'username'=>'root',
            'password'=>'',
            'database'=>'tests'
        ]]]);
        $this->assertEquals(get_env('database.default.username'), 'root');
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

    public function test_class_make()
    {
        factory('waiterphp.core.db.database');
        instance('waiterphp.core.db.database');

        $configs = load_configs(['web.php'], [__DIR__ . '/config']);
        action('waiterphp.core.db.database.register', [$configs['database']['default']]);
    }
}