<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 17:52
 */

namespace Waiterphp\Core;


class Dao
{
    /**
     * 静态管理方法
     */

    // 虚拟表工厂
    public static function newConfig($table = '')
    {
        $daoConfig =  new DaoConfig($table);
        return $daoConfig;
    }
}


/*
 * 虚拟表数据构造类
 */
class DaoConfig
{
    public $table; // 表名
    public $primaryKey; // 主键
    public $database = null;
    public $fields = array(); // 字段基础信息
    public $defaultQuery = array(); // 默认查询
    public $detailFields = array(); // 简要信息中不展示
    public $readonlyFields = array(); // 不可被外部修改的字段
    public $virtualField = array(); // 虚拟字段（外部可见，但内部其实没有）
    public $filters = array(); // 数据过滤器
    public $processing = array(); // 数据处理器
    public $joinTables = array(); // 连接从表
    public $softDeleteField = false; // 软删除标识字段，默认为不存在


    private static $baseFieldType = array(
        'int'=>'number',
        'tinyint'=>'number',
        'smallint'=>'number',
        'bigint'=>'number',
        'varchar'=>'string',
        'text'=>'string',
        'char'=>'string',
        'datetime'=>'date',
        'timestamp'=>'date',
    );
    private static $extendFieldType = array(
        'html'=>array('type'=>'text', 'filter'=>'string', 'params'=>'html'),
        'json'=>array('type'=>'varchar', 'filter'=>'string', 'params'=>'json'),
        'email'=>array('type'=>'varchar', 'filter'=>'regex', 'params'=>'\w+@\w(\.\w+)+'),
    );

    private $defaultFilters = array(
        'empty'=>array('action'=>'regex', 'errorMessage'=>'不能为空！', 'regex'=>'[\w|\W]{1,}'),
        'length'=>array('action'=>'regex', 'errorMessage'=>'长度应该在@min～@max之间!', 'regex'=>'[\w|\W]{@min,@max}', 'min'=>1, 'max'=>255),
        'number'=>array('action'=>'regex', 'errorMessage'=>'必须为数字', 'regex'=>'\d+'),
        'email'=>array('action'=>'regex', 'errorMessage'=>'邮箱格式错误', 'regex'=>'\w+@\w(\.\w+)+'),
    );

    private $pipeline = array(
        'timestamp'=>'timestamp',
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
            'type'=>'int',
            'unsigned'=>true,
            'primaryKey'=>true,
            'pipeline'=>'int',
            'isVirtual'=>false,
        );
    }

    public function setField($field, $args)
    {
        $args = func_get_args();
        $field = array_shift($args);
        if (isset($this->fields[$field])) {
            throw new Exception('field all ready set:'.$field);
        }
//        $this->fields[$field]['trueField'] = $field;
        $this->analyzeFieldArgs($field, $args);
        $this->safeCheck($field);
        $this->appendDefaultFilters($field, $this->fields[$field]);
        if (isset($this->pipeline[$this->fields[$field]['type']])) {
            $pipeline = $this->pipeline[$this->fields[$field]['type']];
            $this->fields[$field]['pipeline'] = $pipeline;
        }
    }

    public function setFieldEnum($field, $enum)
    {
        if (!isset($this->fields[$field])) {
            throw new Exception('not set field:'.$field);
        }
        $this->fields[$field]['enum'] = $enum;
        $this->fields[$field]['type'] = 'enum';
    }

    public function setFieldMap($field, $map)
    {
        $nameField = $field . 'Name';
        $nameFieldName = $this->fields[$field]['name'] . '名';
//        $this->setField($nameField, 'varchar', 255, $nameFieldName);
        $this->virtualField[$nameField] = array(
            'type'=>'map',
            'map'=>$map,
        );
    }

    public function setFieldHtml($field)
    {
        $this->setFilter($field, 'html');
    }

    public function setFieldDefault($field, $value)
    {
        if (!isset($this->fields[$field])) {
            throw new Exception('not set field:'.$field);
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

    // 设置默认查询条件
    public function setDefaultQuery(array $condition)
    {
        $this->defaultQuery = $condition;
    }

    // 设置自定义过滤器
    public function setPipeline(DaoPipeline $Pipeline)
    {

    }

    // 获取字段过滤器，带参数则直接返回对象
    public function getPipeline($field, $PipelineName = '')
    {

    }

    // 设置字段为基本信息不可见
    public function setFieldsIsDetail($fields)
    {
        $fields = func_get_args();
        $fields = implode(',', $fields);
        $fields = explode(',', $fields);
        $this->detailFields = array_merge($this->detailFields, $fields);
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

    public function setFilter($field, $type, $params = '') // email、mobile、json、
    {
        $this->filters[$field][$type] = $params;
    }

    public function getRegex($regexKey) {
        $this->validation = empty($this->validation) ? Config::get('validation') : $this->validation;
        $regexArr = explode('[', $regexKey);
        $regex = $regexArr[0];
        $range = isset($regexArr[1]) ? rtrim($regexArr[1], ']') : '';

        $regexstr = '';
        $message = '';
        if (empty($range) || !$this->validation[$regex]['isLength']) {
            return $this->validation[$regex];
        } elseif (strpos($range, ':') === false) {
            $regexstr = $this->validation[$regex]['regex'] . '{' . $range . '}$';
            $message = $this->validation[$regex]['message'] . ',并且长度必须为' . $range . '位';
        } elseif (strpos($range, ':') !== false) {
            list($min, $max) = explode(':', $range);
            $regexstr = $this->validation[$regex]['regex'] . '{' . (int)$min . ','. $max. '}$';
            $message = !empty($max) && !empty($min) ? ',长度应该为' . $min . '-' .$max. '位' : '';
            $message = empty($max) ? ',长度最少为' . $min . '位' : $message;
            $message = empty($min) ? ',长度最多为' . $max . '位' : $message;
            $message = $this->validation[$regex]['message'] . $message;
        }
        return array('regex'=>$regexstr, 'message'=>$message);
    }

    public function canWork()
    {
        if (empty($this->table)) {
            throw new Exception('not set table');
        }
        if (empty($this->primaryKey)) {
            throw new Exception('primary key not set');
        }
        return true;
    }

    public function setSoftDelete($field)
    {
        if (!isset($this->fields[$field])) {
            throw new Exception('soft delete field not exist:' . $field);
        }
        $this->softDeleteField = $field;
    }

    // 解析字段参数
    public function analyzeFieldArgs($field, $args)
    {
        while (!empty($args)) {
            $arg = array_shift($args);

            // 字符串类型
            if (is_string($arg)) {
                // 基础字段类型识别
                if (isset(self::$baseFieldType[$arg])) {
                    $this->fields[$field]['type'] = $arg;
                    continue;
                }
                // 扩展字段类型识别
                if (isset(self::$extendFieldType[$arg])) {
                    $this->fields[$field]['type'] = self::$extendFieldType[$arg]['type'];
                    $this->filters[$field][self::$extendFieldType[$arg]['filter']] = self::$extendFieldType[$arg]['params'];
                    continue;
                }
                // unsigned识别
                if ($arg == 'unsigned') {
                    $this->fields[$field]['unsigned'] = true;
                    continue;
                }
                // 含有中文的被认为是字段名
                if (is_word($arg)) {
                    $this->fields[$field]['name'] = $arg;
                    continue;
                }
                // 真实字段识别
                if ($pos = strpos($arg, '.')) {
                    $this->fields[$field]['trueField'] = $arg;
                    $table = substr($arg, 0, $pos);
                    $this->fields[$field]['table'] = $table;
                    continue;
                }

                // 前置下划线被认为是过滤器
                if ($arg[0] == '_') {
                    $this->filters[$field]['regex'] = ltrim($arg, '_');
                    continue;
                }
            }
            // 数字认为是长度
            if (is_int($arg)) {
                $this->fields[$field]['length'] = $arg;
                continue;
            }
            // 数据被认为是map
            if (is_array($arg)) {

            }
            // 可调用函数
            if (is_callable($arg)) {
                $params['type'] = '';
                $params['pipeline'] = 'DisplayPipeline';
                $params['call'] = $arg;
                $params['isVirtual'] = true;
                continue;
            }

            throw new Exception('no look:' . $arg);
        }
    }

    private function safeCheck($field)
    {
        $params = $this->fields[$field];
        if ($params['type'] == 'varchar') {
            if (!isset($params['length'])) {
                throw new Exception('varchar mast set length:'.$field);
            }
        }
    }

    private function appendDefaultFilters($field, $params)
    {
        if (!isset($params['default'])) {
            $this->filters[$field][] = $this->defaultFilters['empty'];
        }
        if (isset($params['length']) && $params['type'] == 'varchar') {
            $filter = $this->defaultFilters['length'];
            $filter['max'] = $params['length'];
            $filter = $this->replaceTemplateArgs($filter);
            $this->filters[$field][] = $filter;
        }
        if ($params['type'] == 'int') {
            $this->filters[$field][] = $this->defaultFilters['number'];
        }
//        if ($params['type'] == 'varchar' && !isset($this->filters[$field]['string'])) {
//            $filter = ($params['length'] > 255) ? 'text' : 'string';
//            $this->filters[$field]['string'] = $filter;
//        }
    }

    private function replaceTemplateArgs($filter)
    {
        $response = array();
        foreach ($filter as $key=>$value) {
            $response[$key] = $value;
            foreach ($filter as $replaceKey=>$replaceValue) {
                $response[$key] = str_replace('@' . $replaceKey, $replaceValue, $response[$key]);
            }
        }
        return $response;
    }
}

class DaoFilter
{
    private static $defaultMethod = array('regex'=>1);

    public static function check($action, $value, $params)
    {
        if (is_string($action)) {
            if (!isset(self::$defaultMethod[$action])) {
                throw new Exception('not has action:' . $action);
            }
            return call_user_func(array('DaoFilter', $action), $value, $params);
        }
        if (is_callable($action)) {
            return $action($value, $params);
        }
        return false;
    }

    public static function regex($value, $params)
    {
        $pattern = $params['regex'];
        return preg_match($pattern, $value);
    }
}

abstract class DaoPipeline
{
    abstract public function toShow($value);
    abstract public function toDb($value);

    public static function iteration($values, $config, $direction)
    {
        // 空值处理
        if (empty($values)) {
            return array();
        }
        // 多条记录处理
        if (isset($values[0]) && is_array($values[0])) {
            foreach ($values as $key=>$value) {
                $values[$key] = self::iteration($value, $config, $direction);
            }
            return $values;
        }
        // 单条记录处理
        foreach ($values as $field=>$value) {
            if (isset($config->fields[$field]['pipeline'])) {
                $class = self::fieldInstance($config->fields[$field]['pipeline']);
                $values[$field] = $class->$direction($value);
            }
        }
        return $values;
    }

    private static function fieldInstance($pipeline)
    {
        static $classes = array();
        if (!isset($classes[$pipeline])) {
            $Pipeline = ucfirst($pipeline) . 'Pipeline';
            $classes[$pipeline] = new $Pipeline();
        }
        return $classes[$pipeline];
    }
}

class IntPipeline extends DaoPipeline
{
    public function toShow($value)
    {
        return $value;
    }

    public function toDb($value)
    {
        return (int)$value;
    }
}


class StringPipeline extends DaoPipeline
{
    public function toShow($value)
    {
        return preg_replace("/[\r|\n]+/", '', stripslashes($value));
    }

    public function toDb($value)
    {
        $value = addslashes($value);
        return trim($value);
    }
}

class TextPipeline extends DaoPipeline
{
    public function toShow($value)
    {
        return stripslashes($value);
    }

    public function toDb($value)
    {
        return trim($value);
    }
}


class HtmlPipeline extends DaoPipeline
{
    public function toShow($value)
    {
        return stripslashes($value);
    }

    public function toDb($value)
    {
        $value = addslashes($value);
        return trim($value);
    }
}

class JsonPipeline extends DaoPipeline
{
    public function toShow($value)
    {
        return json_decode($value, true);
    }

    public function toDb($value)
    {
        return json_encode($value);
    }
}

class TimestampPipeline extends DaoPipeline
{
    public function toDb($value)
    {
        return $value;
    }

    public function toShow($value)
    {
        return $value;
    }
}