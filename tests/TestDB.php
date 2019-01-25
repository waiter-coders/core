<?php

namespace Waiterphp\Core\Tests;

class TestDB extends TestCase
{
    public function SetUp()
    {
        set_env(load_configs(['web.php'], [__DIR__ . '/config', __DIR__ . '/../../config']));
        parent::SetUp();
    }

    public function test_fetchData()
    {
        $data = table('article')->select('articleId,userId,title,hit as hit_num')
        ->where([
            'userId'=>1,
            'articleId'=>[1,2,3,4,5,6,7,8],
            'addTime >='=>'2018-01-01 00:00:00',
            'title like'=>'%测试%'
        ])->orderBy('articleId desc')
        ->limit(10)
        ->offset(0)->fetchAll();
        $this->assertTrue(is_array($data) && count($data) > 0);
        $this->assertTrue(is_array($data[0]) && count($data[0]) > 0);
        $this->assertTrue(isset($data[0]['hit_num']));

        $data = table('article')->where(['userId'=>1])->fetchRow();
        $this->assertTrue(is_array($data) && count($data) > 0);
    }

    public function test_update()
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

        // 直接设置更新
        table('article')->where([
            'articleId'=>1
        ])->update([
            'hit=hit+1'
        ]);
        $hit = table('article')->where([
            'articleId'=>1
        ])->fetchColumn('hit');
        $this->assertEquals($hit, $randNum + 1);
    }

    public function test_insert_delete()
    {
        // 插入数据
        $articleId = table('article')->insert([
            'userId'=>2,
            'title'=>'insert data'
        ]);
        $article = table('article')->where([
            'articleId'=>$articleId
        ])->fetchRow();
        $this->assertTrue(!empty($article));
        $this->assertTrue(isset($article['userId']) && isset($article['title']));
        $this->assertEquals($article['userId'], 2);
        $this->assertEquals($article['title'], 'insert data');

        // 删除数据
        table('article')->where([
            'articleId'=>$articleId
        ])->delete();
        $article = table('article')->where([
            'articleId'=>$articleId
        ])->fetchRow();
        $this->assertTrue(empty($article));
    }

    public function test_statistics()
    {
        // 行数
        $num = table('article')->where(['userId'=>1])->count();
        $this->assertEquals($num, 2);
    }

    public function test_group()
    {
        $data = table('article')->select('userId,count(*) as num')->groupBy('userId')->fetchAll();
        $this->assertEquals($data[0]['num'], 2);
    }
} 