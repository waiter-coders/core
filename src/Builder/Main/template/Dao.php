<?php
namespace Model;

class _Model_
{
    public function load()
    {
        // 数据源配置
        $this->daoConfig->setTable('_table_');
        $this->daoConfig->setPrimaryKey('_primaryKey_');
        /* foreach ($fields as $key=>$value) {*/
        $this->daoConfig->setField('_value_field_', '_value_type_', /*if (isset($value['length'])){*/'_value_length_', /* } */'_value_name_');
        /* } */
    }
}
