<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\database\Base;

class SelectTest extends Base
{
    public function testSimpleSelect()
    {
        $data = table('article')
            ->select('title, hit')
            ->orderBy('hit desc')
            ->limit(2)
            ->offset(1)
            ->fetchAll();
        $this->assertTrue(is_array($data) && count($data) == 2);
        $this->assertTrue(array_keys($data[0]) == ['title', 'hit']);
        $this->assertTrue($data[0]['hit'] >= $data[0]['hit']);
    }

    public function testWhere()
    {
        $data = table('article')->where(['userId'=>1])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['hit >'=>10])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['hit >='=>10])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['hit <'=>10])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['hit <='=>10])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['articleId'=>[1, 2, 3, 4]])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['articleId in'=>[1, 2, 3, 4]])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['articleId not in'=>[1, 2, 3, 4]])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['addTime between'=>['2019-01-01 00:00:00', '2020-12-16 13:45:23']])->fetchAll();
        $this->assertTrue(is_array($data));
        $data = table('article')->where(['title like "%测试%"'])->fetchAll();
        $this->assertTrue(is_array($data));
    }

    public function testOtherSelect()
    {
        $data = table('article')->where(['userId'=>1])->fetchRow();
        $this->assertTrue(isset($data['title']));
        $data = table('article')->where(['userId'=>1])->fetchColumn('title');
        $this->assertTrue(is_string($data));
        $data = table('article')->where(['userId'=>1])->fetchColumns('title');
        $this->assertTrue(is_string($data[0]));
    }

    public function testPrint()
    {
        $query = table('article');
        $sql = $query->where([
            'userId'=>1
        ])->sql();
        $this->assertEquals($sql, 'select * from article where (userId = 1) limit 0, 10000;');
        $query->where([
            'userId'=>1,
        ])->update([
            'hit'=>999
        ]);
        $this->assertEquals($query->sql(), 'update article set hit = 999 where (userId = 1)');
    }
} 