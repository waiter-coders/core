<?php
namespace waiterphp\core\tests\database\table;

use waiterphp\core\database\TableTrait;

class ArticleDao
{
    use TableTrait;

    protected function setConfig($config)
    {
        $config->setTable('article');
        $config->setPrimaryKey('articleId');
        $config->setField('title', 'string', 50); // 标题
        $config->setField('addTime', 'datetime'); // 添加时间
        $config->setField('hit', 'number'); // 添加时间
    }
}


