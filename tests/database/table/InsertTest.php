<?php
namespace waiterphp\core\tests\database\table;
use waiterphp\core\tests\database\Base;
use \waiterphp\core\tests\database\table\ArticleDao;

class InsertTest extends Base
{

    public function SetUp()
    {
        parent::SetUp();
        $this->table = new ArticleDao();
    }

    public function testDelete()
    {
        $result = $this->table->insert([
            'userId'=>2,
            'title'=>'insert data'
        ]);
        // $this->assertTrue($result > 0);
    }
    
} 