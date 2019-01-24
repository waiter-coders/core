<?php

namespace Waiterphp\Core\Tests;

class TestFilter extends TestCase
{
    private $filterData = [];

    public function SetUp()
    {
        parent::SetUp();
        $this->filterData = [
            'name'=>'account',
        ];
    }

    public function test_filter()
    {
        $name = filter($this->filterData)->getString('name');
        $this->assertEquals($name, 'account');

        $notHasKey = filter($this->filterData)->getString('other', '');
        $this->assertEquals($notHasKey, '');
    }
}