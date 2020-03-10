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
            ->fields('title, hit')
            ->orderBy('hit desc')
            ->limit(2)
            ->offset(1)
            ->fetchAll();
        $this->assertTrue(is_array($data) && count($data) == 2);
        $this->assertTrue(array_keys($data[0]) == ['title', 'hit']);
        $this->assertTrue($data[0]['hit'] >= $data[0]['hit']);
    }

    public function testListAfterId()
    {
        $data = $this->table->listAfterId(2, 3);
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
} 