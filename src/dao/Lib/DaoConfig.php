<?php


namespace Waiterphp\Dao\Lib;
/*
 * 虚拟表数据构造类
 */
class DaoConfig
{
    // 数据库连接信息
    public $table; // 表名
    public $primaryKey; // 主键
    public $database = null;
    public $joinTables = array(); // 连接从表

    // 字段信息
    public $fields = array(); // 字段基础信息
    public $fieldsGroups = array(); // 字段分组
    public $readonlyFields = array(); // 不可被外部修改的字段
    public $virtualField = array(); // 虚拟字段（外部可见，但内部其实没有）
    public $fieldsFilters = array(); // 数据过滤器

    // query相关
    public $defaultQuery = array(); // 默认查询
    public $softDeleteFields = false; // 软删除标识字段，默认为不存在

    //
    private static $baseType = array(
        'number'=>array('action'=>'regex', 'errorMessage'=>'必须为数字', 'regex'=>'\d+'), // 对应tinyint int 等
        'string'=>array('action'=>'regex', 'errorMessage'=>'长度应该在@min～@max之间!', 'regex'=>'[\w|\W]{@min,@max}', 'min'=>1, 'max'=>255), // varchar char
        'text'=>array('type'=>'text', 'filter'=>'string', 'params'=>'html'), // 没有html标签，
        'html'=>array('action'=>'regex', 'errorMessage'=>'不能为空！', 'regex'=>'[\w|\W]{1,}'), //
        'select'=>1, // 单选
        'multiSelect'=>1, // 多选
        'linkSelect'=>1, // 多字段层级联动
        'date'=>1, //
        'datetime'=>1,
        'month'=>1,
        'year'=>1,
        'image'=>1, // path标准化
        'file'=>1, // path标准化
        'phone'=>1, // 手机号
        'email'=>array('action'=>'regex', 'errorMessage'=>'邮箱格式错误', 'regex'=>'\w+@\w(\.\w+)+'),
        'json'=>array('type'=>'varchar', 'filter'=>'string', 'params'=>'json'),

        // json path url
    );

    private static $baseFilters = array(

    );

    public function __construct($table = '')
    {
        $this->setTable($table);
    }

    public function setDatabase(array $database)
    {
        $this->database = $database;
    }

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function setPrimaryKey($primaryKey)
    {
        $this->primaryKey = $primaryKey;
        $this->fields[$primaryKey] = array(
            'name'=>'id',
            'type'=>'number',
            'primaryKey'=>true,
        );
    }

    public function setField($field, $type, $args = array())
    {
        $args = func_get_args();
        $field = array_shift($args);
        list($trueField, $field) = $this->formatField($field);
        $type = array_shift($args);        
        assertOrException(!isset($this->fields[$field]), 'field all ready set:'.$field);
        assertOrException(isset(self::$baseType[$type]), 'field type not exist:' . $type);
        $this->fields[$field] = array('field'=>$field, 'type'=>$type);
        $this->fields[$field] = array_merge($this->fields[$field], $this->analyzeFieldArgs($type, $args));
        if (!empty($trueField)) {
            $this->fields[$field]['trueField'] = $trueField;
        }
        return $this;
    }

    public function setFieldDefault($field, $value)
    {
        if (!isset($this->fields[$field])) {
            throw new \Exception('not set field:'.$field);
        }
        $this->fields[$field]['default'] = $value;
    }

    // 设置join表
    public function setJoinTable($joinTable, $mainField, $joinField = null)
    {
        list($table, $tableLabel) = explode(' ', $joinTable . ' ', 2);
        $table = trim($table);$tableLabel = trim($tableLabel);
        $tableLabel = empty($tableLabel) ? $table : $tableLabel;
        $joinField = empty($joinField) ? $mainField : $joinField;
        $this->joinTables[$tableLabel] = array('table'=>$table, 'mainField'=>$mainField, 'joinField'=>$joinField);
    }

    public function leftJoin($joinTable, $mainField, $joinField = null)
    {
        $this->setJoinTable($joinTable, $mainField, $joinField);
    }

    // 设置默认查询条件
    public function setDefaultQuery(array $condition)
    {
        $this->defaultQuery = $condition;
    }

    // 设置信息不可以被修改
    public function setFieldsReadonly($fields)
    {
        $fields = func_get_args();
        $fields = implode(',', $fields);
        $fields = explode(',', $fields);
        $fields = array_flip($fields);
        $this->readonlyFields = array_merge($this->readonlyFields, $fields);
    }

    public function setFilter($field, $input, $inputParams = array(), callable $output = null, $outputParams = array())
    {
        assertOrException((is_string($input) || (is_callable($input) && is_callable($output))), 'filter set error');
        $this->fieldsFilters[$field][] = array('input'=>$input, 'inputParams'=>$inputParams, 'output'=>$output, 'outputParams'=>$outputParams);
    }

    public function getFilterByName($filterName)
    {
        return array(function(){}, function(){});
    }

    public function setSoftDelete($field)
    {
        if (!isset($this->fields[$field])) {
            throw new \Exception('soft delete field not exist:' . $field);
        }
        $this->softDeleteFields = $field;
    }

    // 解析字段参数
    public function analyzeFieldArgs($type, $args)
    {
        $params = array();
        while (!empty($args)) {
            $arg = array_shift($args);

            // 字符串类型
            if (is_string($arg)) {
                // http为baseUrl(临时添加)
                if (substr($arg, 0, 4) == 'http') {
                    $params['baseUrl'] = $arg;
                    continue;
                }

                // unsigned识别
                if ($arg == 'unsigned') {
                    $params['unsigned'] = true;
                    continue;
                }
                // 含有中文的被认为是字段名
                if (preg_match("/[\x7f-\xff]/", $arg)) {
                    $params['name'] = $arg;
                    continue;
                }

                // 前置下划线被认为是过滤器
                if ($arg[0] == '_') {
                    $this->fieldsFilters[$type]['regex'] = ltrim($arg, '_');
                    continue;
                }
            }
            // 数字认为是长度
            if (is_numeric($arg)) {
                $params['length'] = $arg;
                continue;
            }
            // 数据被认为是map
            if (is_array($arg)) {
                $params['map'] = $arg;
                continue;
            }
            // 可调用函数
            if (is_callable($arg)) {
                $params['type'] = '';
                $params['filter'] = 'DisplayFilter';
                $params['call'] = $arg;
                $params['isVirtual'] = true;
                continue;
            }

            throw new \Exception('no look:' . json_encode($arg));
        }
        return $params;
    }

    private function formatField($field)
    {
        if (strpos($field, ' ') === false) {
            return array('', $field);
        } else {
            $field = str_replace(' as ', ' ', $field);
            $field = trim(preg_replace('/[ ]{2,}/i', ' ', $field));
            return explode(' ',  $field, 2);
        }
    }
}
