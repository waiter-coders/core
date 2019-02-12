<?php

namespace Waiterphp\Core\Tests\Builder\Main;

use Waiterphp\Core\Tests\TestCase as TestCase;

use Waiterphp\Core\Builder\Main\Dao as BuilderDao;
use Waiterphp\Core\File\File as File;

class TestDao extends TestCase
{

    public function SetUp()
    {
        parent::SetUp();
        $this->builder = new BuilderDao('/tmp');
    }

    public function test_build()
    {
        $daoFile = '/tmp/Model/ExamChoice.php';
        is_file($daoFile) && unlink($daoFile);

        $this->builder->build([
            'table'=>'exam_choice'
        ]);
        $this->assertTrue(is_file($daoFile));
        $content = file_get_contents($daoFile);
        // $this->assertEquals($content, $this->articleContent());
        
    }

    private function articleContent()
    {
        return '';
    }
}