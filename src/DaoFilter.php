<?php
namespace Waiterphp\Core;


class DaoFilter
{
    private $daoConfig;

    public function __construct(DaoConfig $daoConfig)
    {
        $this->daoConfig = $daoConfig;
    }

    public function input($field, $value = '')
    {
        return $this->filterField($field, $value, 'input');
    }

    public function output($field, $value = '')
    {
        return $this->filterField($field, $value, 'output');
    }

    private function filterField($field, $value, $type)
    {
        // 兼容多字段同时处理
        if (is_array($field)) {
            foreach ($field as $key=>$value) {
                $field[$key] = $this->filterField($key, $value, $type);
            }
            return $field;
        }
        // 过滤数据
        else {
            $value = is_array($value) ? json_encode($value) : $value;
            return isset($this->daoConfig->fieldsFilters[$field]) ?
                DaoFilter_Factory::filter($this->daoConfig->fieldsFilters[$field], $value, $type) : $value;
        }
    }
}

class DaoFilter_Factory
{
    public static function filter($filter, $value, $type)
    {
        return $value;
    }

    private static function getFilter($filter, $type)
    {

        $input = $filter['input'];
        $output = $filter['output'];
        if (is_string($input)) {
            list($input, $output) = self::getSystemFilters($input);
        }
        return $type == 'output' ? $output : $input;
    }

    private function getSystemFilters($name)
    {
        return array(function(){}, function(){});
    }
}

class DaoFilter_Number
{
    public static function filter($value)
    {
        return (int) $value;
    }
}

class DaoFilter_String
{
    public static function filter($value)
    {
        return preg_replace("/[\r|\n]+/", '', stripslashes($value));
    }
}

class DaoFilter_String_Output
{

    public static function filter($value)
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

class DaoFilter_Json
{
    public static function filter($value)
    {
        return json_encode($value);
    }
}

class DaoFilter_Json_Output
{
    public static function filter($value)
    {
        return json_decode($value, true);
    }
}



class DaoFilter_Regex
{
    public static function filter($value, $params = '')
    {
        return $value;
    }

    public static function regex($value, $params)
    {
        $pattern = $params['regex'];
        return preg_match($pattern, $value);
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


