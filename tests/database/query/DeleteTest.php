<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\TestCase;

class DeleteTest extends TestCase
{
    public function testDelete()
    {
        try {

        
        // 插入数据
        $articleId = table('article')->insert([
            'userId'=>666,
            'title'=>'insert data'
        ]);
        // 删除数据
        $rows = table('article')->where([
            'userId'=>666
        ])->delete();
        $this->assertEquals($rows, 1);
        } catch (\Exepection $e) {
            var_dump($e);
        }
    }
}


