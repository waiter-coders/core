<?php
namespace waiterphp\core\database\table;
/*
 * 虚拟表数据构造类
 */
class Config
{
    // 数据库连接信息
    private $table; // 表名
    private $primaryKey; // 主键
    private $name = ''; // 数据库配置名

    // 字段信息
    private $fields = []; // 字段基础信息
    // private $fieldsGroups = []; // 字段分组
    private $readonlyFields = []; // 不可被外部修改的字段
    // private $fieldsFilters = []; // 数据过滤器

    // query相关
    private $defaultWhere = []; // 默认查询

    private static $typeMap = [
        'number'=>'',
        'string'=>'',
        'datetime'=>'',
        'json'=>''
    ];

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function setName(array $name)
    {
        $this->name = $name;
    }

    public function setPrimaryKey($primaryKey, $type = 'number', $rules = 'unsigned')
    {
        $this->primaryKey = $primaryKey;
        $this->fields[$primaryKey] = [
            'type'=>$type,
            'primaryKey'=>true,
            'rules'=>$rules
        ];
    }

    public function setField($field, $type, $rules = [])
    {
        $rules = func_get_args();
        assert_exception(count($rules) >= 2, 'field args error:' . $field);
        $field = array_shift($rules);
        $type = array_shift($rules);        
        assert_exception(!isset($this->fields[$field]), 'field allready set:'.$field);
        assert_exception(isset(self::$typeMap[$type]), 'field type not exist:'  . $type . ' in ' . $field);        
        $this->fields[$field] = ['field'=>$field, 'type'=>$type];
        $this->fields[$field] = array_merge($this->fields[$field], $this->analyzeField($type, $rules));
    }

    // 设置默认查询条件
    public function setDefaultWhere(array $where)
    {
        $this->defaultWhere = $where;
    }

    public function defaultWhere()
    {
        return $this->defaultWhere;
    }

    // 设置信息不可以被修改
    public function setReadonly($fields)
    {
        $this->readonlyFields = $fields;
    }

    public function fields()
    {
        return array_keys($this->fields);
    }

    public function field($field)
    {
        return $this->fields[$field];
    }

    public function table()
    {
        return $this->table;
    }

    public function name()
    {
        return $this->name;
    }

    public function primaryKey()
    {
        return $this->primaryKey;
    }

    // 解析字段参数
    private function analyzeField($type, $args)
    {
        $params = [];
        while (!empty($args)) {
            $arg = array_shift($args);
            // 空字符串跳过
            if (empty($arg)) {
                continue;
            }
            // 字符串类型
            if (is_string($arg)) {
                // http为baseUrl(临时添加)
                // if (substr($arg, 0, 4) == 'http') {
                //     $params['baseUrl'] = $arg;
                //     continue;
                // }

                // unsigned识别
                if ($arg == 'unsigned') {
                    $params['unsigned'] = true;
                    continue;
                }

                // // 前置下划线被认为是过滤器
                // if ($arg[0] == '_') {
                //     $this->fieldsFilters[$type]['regex'] = ltrim($arg, '_');
                //     continue;
                // }
            }
            // 数字认为是长度
            if (is_numeric($arg)) {
                $params['length'] = $arg;
                continue;
            }
            // 数据被认为是map
            if (is_array($arg)) { // TOCO default map
                $params['map'] = $arg;
                continue;
            }
            // // 可调用函数
            // if (is_callable($arg)) {
            //     $params['type'] = '';
            //     $params['filter'] = 'DisplayFilter';
            //     $params['call'] = $arg;
            //     $params['isVirtual'] = true;
            //     continue;
            // }

            throw new \Exception('dao field args error:' . json_encode($arg));
        }
        return $params;
    }
}
