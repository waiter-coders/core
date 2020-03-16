<?php
namespace waiterphp\core\tests\database\table;
use waiterphp\core\tests\database\Base;
use \waiterphp\core\tests\database\table\ArticleDao;

class UpdateTest extends Base
{

    public function SetUp()
    {
        parent::SetUp();
        $this->table = new ArticleDao();
    }
    

    public function testUpdate()
    {
        $result = $this->table->updateById(1, [
            'title'=>'dao update'
        ]);
        // $this->assertTrue($result > 0);
    }
} 