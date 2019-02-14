<?php

namespace Waiterphp\Core\Tests;

class TestCache extends TestCase
{
    public function test_fetchAll()
    {
        $data = table('article')->where(['userId'=>1])->fetchAll();
        $this->assertTrue(is_array($data) && count($data) > 0);
        $this->assertTrue(is_array($data[0]) && count($data[0]) > 0);
    }
}