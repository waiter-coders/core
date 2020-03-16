<?php
namespace waiterphp\core\tests\database\table;
use waiterphp\core\tests\database\Base;
use waiterphp\core\tests\database\table\ArticleDao;

class SelectTest extends Base
{

    public function SetUp()
    {
        parent::SetUp();
        $this->table = new ArticleDao();
    }
    public function testSimpleSelect()
    {
        $data = $this->table
            ->select('title, hit')
            ->orderBy('hit desc')
            ->limit(2)
            ->offset(1)
            ->fetchAll();
        $this->assertTrue(is_array($data) && count($data) == 2);
        $this->assertTrue(array_keys($data[0]) == ['title', 'hit']);
        $this->assertTrue($data[0]['hit'] >= $data[0]['hit']);
    }

    public function testFetchByIds()
    {
        $data = $this->table->fetchByIds([2, 3]);
        $this->assertTrue(is_array($data) && count($data) == 2);
    }

    public function testFetchAfterId()
    {
        $data = $this->table->fetchAfterId(2, 3);
        $this->assertTrue(is_array($data) && count($data) == 3);
    }

    public function testInfoById()
    {
        $data = $this->table->infoById(2);
        $this->assertEquals(array_keys($data), ['articleId', 'title', 'addTime', 'hit']);
    }

    public function testInfoByField()
    {
        $data = $this->table->infoByField('userId', 1);
        $this->assertEquals(array_keys($data), ['articleId', 'title', 'addTime', 'hit']);
    }

    public function testMuliSelect()
    {
        $data = $this->table->where(['userId'=>1])->fetchAll();
        $data = $this->table->where(['userId !='=>1])->fetchAll();
        $this->assertTrue(is_array($data) && count($data) > 0);
        $sql = $this->table->select('title')->where(['articleId'=>4])->sql();
        echo $sql;
    }
} 