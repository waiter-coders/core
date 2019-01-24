<?php

namespace Waiterphp\Core\Tests;

class TestDB extends TestCase
{
    public function SetUp()
    {
        set_env(load_configs(['web.php'], [__DIR__ . '/config', __DIR__ . '/../../config']));
        parent::SetUp();
    }

    public function test_fetchAll()
    {
        $data = table('article')->where(['userId'=>1])->fetchAll();
        $this->assertTrue(is_array($data) && count($data) > 0);
        $this->assertTrue(is_array($data[0]) && count($data[0]) > 0);
    }
}