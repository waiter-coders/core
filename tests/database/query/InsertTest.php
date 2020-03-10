<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\TestCase;

class InsertTest extends TestCase
{
    public function testInsert()
    {
        // 插入数据
        $articleId = table('article')->insert([
            'userId'=>2,
            'title'=>'insert data'
        ]);
        $this->assertTrue(is_numeric($articleId) && $articleId > 0);
    }
}


