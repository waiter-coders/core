<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\TestCase;

class UpdateTest extends TestCase
{
    public function testSimpleUpdate()
    {
        // 更新数据
        $randNum = mt_rand(1, 10000);
        table('article')->where([
            'articleId'=>1
        ])->update([
            'hit'=>$randNum
        ]);

        $hit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        $this->assertEquals($hit, $randNum);
    }

    public function testStringUpdate()
    {        
        $lastHit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        // 直接设置更新
        table('article')->where([
            'articleId'=>1
        ])->update([
            'hit=hit+1'
        ]);
        $hit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        $this->assertEquals($hit, $lastHit + 1);
    }

    public function testIncrement()
    {        
        $lastHit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        table('article')->where([
            'articleId'=>1
        ])->increment('hit', 6);
        $hit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        $this->assertEquals($hit, $lastHit + 6);
    }

    public function testDecrement()
    {        
        $lastHit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        table('article')->where([
            'articleId'=>1
        ])->decrement('hit', 2);
        $hit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        $this->assertEquals($hit, $lastHit - 2);
    }
}


