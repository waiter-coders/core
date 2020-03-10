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
    
} 