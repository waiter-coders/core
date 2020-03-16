<?php
namespace waiterphp\core\tests\database\table;
use waiterphp\core\tests\database\Base;
use \waiterphp\core\tests\database\table\ArticleDao;

class DeleteTest extends Base
{

    public function SetUp()
    {
        parent::SetUp();
        $this->table = new ArticleDao();
    }
    
    public function testDelete()
    {
        $result = $this->table->deleteById(1);
        // $this->assertTrue($result > 0);
    }
} 