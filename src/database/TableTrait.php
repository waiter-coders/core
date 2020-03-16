<?php
namespace waiterphp\core\database;

use waiterphp\core\database\table\Config;
use waiterphp\core\database\table\Filter;
use \waiterphp\core\database\query\Query;

/**
 * 1. 实现基于table结构的更加便捷的操作
 * 2. 实现基于table结构的过滤校验
 * 3. 不支持join语句
 */

trait TableTrait
{
    private $config; // 配置
    // private $filter; // 过滤器
    private $query; // 查询
    private $where = [];


    public function __construct()
    {
        $this->config = new Config();
        $this->setConfig($this->config);
        assert_exception(!empty($this->config->table()), 'not set table');
        assert_exception(!empty($this->config->primaryKey()), 'primary key not set');
        $this->filter = new Filter($this->config);
        $this->query = new Query($this->config->table(), $this->config->name());
        $this->clearQuery();

    }

    abstract protected function setConfig(Config $config);


    /************************
     * 连续查询条件
     ***********************/

    public function select($fields)
    {
        $this->query->select($fields);
        return $this;
    }

    public function where($where)
    {
        $this->where = $where;
        return $this;
    }

    // 设置排序
    public function orderBy($orderBy)
    {
        $this->query->orderBy($orderBy);
        return $this;
    }

    public function limit($limit)
    {
        $this->query->limit($limit);
        return $this;
    }

    public function offset($offset)
    {
        $this->query->offset($offset);
        return $this;
    }

    public function fetchAll()
    {
        $data = $this->query()->fetchAll();        
        $data = $this->formatArray($data);
        $this->clearQuery();
        return $data;
    }

    public function fetchRow() // 过滤标准化TODO
    {
        $row = $this->query()->fetchRow();
        $row = $this->formatRow($row);
        $this->clearQuery();
        return $row;
    }

    
    public function fetchColumn($column)// 过滤标准化TODO
    {
        $data = $this->query()->fetchColumn($column);
        $data = $this->formatField($column, $data);
        $this->clearQuery();
        return $data;
    }

    public function fetchColumns($column)// 过滤标准化TODO
    {
        $data = $this->query()->fetchColumns($column);
        $data = array_map(function($row) use ($column){
            return $this->formatField($column, $row);
        }, $data);
        $this->clearQuery();
        return $data;
    }    

    public function fetchByIds($ids)
    {
        $this->where[$this->config->primaryKey()] = $ids;
        return $this->fetchAll();
    }

    // 获取大于某主键id值的列表数据
    public function fetchAfterId($id, $limit)
    {
        $this->where[$this->config->primaryKey() . ' >'] = $id;
        $this->query->limit($limit);
        return $this->fetchAll();
    }

    // 获取小于某主键id值的列表数据TODO
    // public function listBeforeId($id)
    // {
    //     $this->where[$this->config->primaryKey() . ' <'] = $id;
    //     return $this->query()->fetchAll();
    // }

    // 根据主键id获取单条记录
    public function infoById($id)
    {
        $this->where[$this->config->primaryKey()] = $id;
        return $this->fetchRow();
    }

    // 根据某个字段的值获取单条记录
    public function infoByField($field, $value) // TODO 使用返回
    {
        $this->where[$field] = $value;
        return $this->fetchRow();
    }

    public function count()
    {
        $data = $this->query()->count();
        $this->clearQuery();
        return $data;
    }

    private function query()
    {
        $where = $this->config->defaultWhere();
        $where = array_merge($where, $this->where);
        return $this->query->where($where);
    }

    /*****************************
     * 数据操作方法
     *****************************/

    // 更新信息??暂不可用
    public function update($update)
    {
        $update = $this->filter->input($update);
        $rowCount = $this->query()->update($update);
        $this->clearQuery();
        return $rowCount;
    }

    // 根据主键id更新信息
    public function updateById($id, $update)
    {
        $this->where[$this->config->primaryKey()] = $id;
        return $this->update($update);
    }

    // 根据某一字段值更新信息
    public function updateField($id, $key, $value)
    {
        return $this->updateById($id, [
            $key=>$value,
        ]);
    }

    // 添加新记录
    public function insert($insert)
    {
        $insert = $this->filter->input($insert);
        $insertId = $this->query()->insert($insert);
        $this->clearQuery();
        return $insertId;
    }

    // 根据Id更新和替换
    // public function replaceById($id, $refresh)
    // {
    //     $hasId = $this->getRow([
    //         $this->primaryKey=>$id,
    //     ]);
    //     if ($hasId) {
    //         return $this->updateById($id, $refresh);
    //     } else {
    //         return $this->insert($refresh);
    //     }
    // }

    // 删除新纪录
    public function deleteById($id)
    {
        $this->where[$this->config->primaryKey()] = $id;
        $result = $this->query()->delete();
        $this->clearQuery();
        return $result;
    }

    public function deleteByIds($ids)
    {
        $this->where[$this->config->primaryKey()] = $ids;
        $result = $this->query()->delete();
        $this->clearQuery();
        return $result;
    }

    public function sql()
    {
        return $this->query()->sql();
    }

    public function config()
    {
        return $this->config;
    }

    private function formatArray($array)
    {
        $result = [];
        foreach ($array as $row) {
            $result[] = $this->formatRow($row);
        }
        return $result;
    }

    private function formatRow($row)
    {
        $newRow = [];
        foreach ($row as $field=>$value) {
            $newRow[$field] = $this->formatField($field, $value);
        }
        return $newRow;
    }

    private function formatField($field, $value)
    {
        $config = $this->config->field($field);
        switch ($config['type']) {
            case 'number':
                return (int) $value;
                break;
            case 'json':
                return json_decode($value, true);
                break;
            default:
            return $value;
        }
    }

    private function clearQuery()
    {
        $this->query->select($this->config->fields()); //TODO
        $this->where = [];
    }
}


