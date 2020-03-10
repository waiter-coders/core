<?php
namespace waiterphp\core\tests\database\query;
use waiterphp\core\tests\TestCase;

class StatisticsTest extends TestCase
{
    public function testStatistics()
    {
        // 行数
        $num = table('article')->where(['userId'=>1])->count();
        $this->assertTrue(is_numeric($num));
        $num = table('article')->where(['userId'=>1])->count('articleId');
        $this->assertTrue(is_numeric($num));
        $num = table('article')->where(['userId'=>1])->max('hit');
        $this->assertTrue(is_numeric($num));
        $num = table('article')->where(['userId'=>1])->min('hit');
        $this->assertTrue(is_numeric($num));
        $num = table('article')->where(['userId'=>1])->avg('hit');
        $this->assertTrue(is_numeric($num));
        $num = table('article')->where(['userId'=>1])->sum('hit');
        $this->assertTrue(is_numeric($num));
    }
} 