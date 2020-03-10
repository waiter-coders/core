<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\TestCase;

class GroupTest extends TestCase
{
    public function testGroup()
    {
        $data = table('article')->fields('userId,count(*) as num')->groupBy('userId')->fetchAll();
        $this->assertTrue(isset($data[0]['num']));
    }

    public function testHaving()
    {
        $data = table('article')->fields('userId,count(*) as num')->groupBy('userId')->having(' count(*) > 2')->fetchAll();
        $this->assertTrue(isset($data[0]['num']));
    }
} 