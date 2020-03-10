<?php
namespace waiterphp\core\database;

use waiterphp\core\database\table\Config as Config;
use waiterphp\core\database\table\Filter as Filter;
use waiterphp\core\database\table\Transfer as Transfer;

/**
 * 1. 实现基于table结构的更加便捷的操作
 * 2. 实现基于table结构的过滤校验
 * 3. 不支持join语句
 */

trait TableTrait
{
    protected $config; // 配置
    private $transfer; // 信息转换
    private $filter; // 过滤器
    private $pipelines; // 数据处理通道
    private $tableFields = [];
    private $query = [];
    private $defaultQuery = ['fields'=>'main', 'where'=> [], 'limit'=>'10000', 'offset'=>0, 'orderBy'=>''];

    public function __construct()
    {
        $this->config = new Config();
        $this->setConfig($this->config);
        assert_exception(!empty($this->config->table), 'not set table');
        assert_exception(!empty($this->config->primaryKey), 'primary key not set');
        $this->transfer = new Transfer($this->config);
        $this->filter = new Filter($this->config);
    }

    abstract protected function setConfig(Config $config);


    /************************
     * 连续查询条件
     ***********************/

    public function fields($fields)
    {
        $this->query['fields'] = $fields;
        return $this;
    }

    public function where($where)
    {
        $this->query['where'] = $where;
        return $this;
    }

    // 设置排序
    public function orderBy($orderBy)
    {
        $this->query['orderBy'] = $orderBy;
        return $this;
    }

    public function limit($limit)
    {
        $this->query['limit'] = $limit;
        return $this;
    }

    public function offset($offset)
    {
        $this->query['offset'] = $offset;
        return $this;
    }

    // public function partBy($column, callable $method)
    // {
    //     $pattern = '/' . $column . ' = (\d+)/i';
    //     $hasColumn = preg_match($pattern, $this->where, $matches);
    //     assert_exception($hasColumn, 'can not find part column ' . $column);
    //     $partId = $matches[1];
    //     $suffix = $method($partId);
    //     $this->mainTable .= '_' . $suffix;
    //     return $this;
    // }

    /************************
     * 查询方法
     ***********************/
    public function fetchAll()
    {
        $data = $this->query()->fetchAll();
        $data = $this->formatArray($data);
        return $data;
    }

    public function fetchRow() // 过滤标准化TODO
    {
        $row = $this->query()->fetchRow();
        $row = $this->formatRow($row);
        return $row;
    }

    
    public function fetchColumn($column)// 过滤标准化TODO
    {
        return $this->query()->fetchColumn();
    }

    public function fetchColumns($column)// 过滤标准化TODO
    {
        return $this->query()->fetchColumns();
    }    

    public function fetchByIds($ids)
    {
        $this->query['where'][$this->primaryKey()] = $ids;
        return $this->fetchAll();
    }

    // 获取大于某主键id值的列表数据
    public function fetchAfterId($id, $limit)
    {
        $this->query['where'][$this->primaryKey() . ' >'] = $id;
        $this->query['limit'] = $limit;
        return $this->fetchAll();
    }

    // 获取小于某主键id值的列表数据TODO
    // public function listBeforeId($id)
    // {
    //     $this->query['where'][$this->primaryKey() . ' <'] = $id;
    //     return $this->query()->fetchAll();
    // }

    // 根据主键id获取单条记录
    public function infoById($id)
    {
        $this->query['where'][$this->primaryKey()] = $id;
        return $this->fetchRow();
    }

    // 根据某个字段的值获取单条记录
    public function infoByField($field, $value) // TODO 使用返回
    {
        $this->query['where'][$field] = $value;
        return $this->fetchRow();
    }

    // public function appendInfoByIds($fields, array &$data, $dataField = null, $joinField = null)
    // {
    //     if (empty($dataField)) {
    //         $dataField = $this->config->primaryKey;
    //     }
    //     if (empty($joinField)) {
    //         $joinField = $this->config->primaryKey;
    //     }

    //     // 检查是否有字段重名
    //     $fieldsArray = explode(',', $fields);
    //     foreach ($fieldsArray as $field) {
    //         if (isset($data[0][$field])) {
    //             throw new \Exception('name exist');
    //         }
    //     }

    //     $query = table($this->config->table, $this->config->database);
    //     foreach ($data as $key=>$value) {
    //         if (!isset($value[$dataField])) {
    //             throw new \Exception('filed not exist');
    //         }
    //         $record = $query->getRow([
    //             $joinField=>$value[$dataField],
    //         ]);
    //         $data[$key] = $value + $record;
    //     }
    //     return true;
    // }

    public function count()
    {
        return (int) $this->query()->count();
    }

    public function query()
    {
        // 获取查询信息
        $query = array_merge($this->defaultQuery, $this->query); $this->query = [];
        // dao转化为db数据
        $select = $this->transfer->getTrueFields($query['fields']);
        $where = $this->transfer->getQueryWhere($query['where']);
        $queryTables = $this->transfer->getQueryTables($select, $where);
        $orderBy = $this->transfer->getQueryOrder($query['orderBy']);
        return $queryTables->fields($select)->where($where)->orderBy($orderBy)->limit($query['limit'])->offset($query['offset']);
    }

    /*****************************
     * 数据操作方法
     *****************************/

    // 更新信息??暂不可用
    public function update($update)
    {
        $query = array_merge($this->defaultQuery, $this->query); $this->query = [];
        $where = $this->transfer->getQueryWhere($query['where']);

        $update = $this->filter->input($update);
        $update = $this->groupByTables($update);
        foreach ($update as $table=>$data) {
            $idField = $this->config->primaryKey;
            if (isset($this->config->joinTables[$table])) { // join表转化为真表
                $idField = $this->config->joinTables[$table]['joinField'];
                $table = $this->config->joinTables[$table]['table'];
            }
            table($table)->where([$idField=>$id])->update($data);
        }
        return true;
    }

    // 根据主键id更新信息
    public function updateById($id, $update)
    {
        $update = $this->filter->input($update);
        $update = $this->groupByTables($update);
        foreach ($update as $table=>$data) {
            $idField = $this->config->primaryKey;
            if (isset($this->config->joinTables[$table])) { // join表转化为真表
                $idField = $this->config->joinTables[$table]['joinField'];
                $table = $this->config->joinTables[$table]['table'];
            }
            table($table)->where([$idField=>$id])->update($data);
        }
        return true;
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
        $insert = array_merge($this->getDefaultValues(), $insert);
        $insert = $this->filter->input($insert);
        $insert = $this->groupByTables($insert);
        $mainInsert = $insert[$this->config->table];
        $query = table($this->config->table, $this->config->database);
        $mainId = $query->insert($mainInsert);
        unset($insert[$this->config->table]);
        foreach ($insert as $table=>$data) {
            if (!isset($this->config->joinTables[$table])) { // join表转化为真表
                throw new \Exception('not set join info'.$table);
            }
            $join = $this->config->joinTables[$table];
            $data[$join['joinField']] = $mainId;
            table($join['table'])->insert($data);
        }
        return $mainId;
    }

    // 根据Id更新和替换
    public function replaceById($id, $refresh)
    {
        $hasId = $this->getRow([
            $this->primaryKey=>$id,
        ]);
        if ($hasId) {
            return $this->updateById($id, $refresh);
        } else {
            return $this->insert($refresh);
        }
    }

    // 删除新纪录
    public function deleteById($id)
    {
        // 软删除
        if (!empty($this->config->softDeleteFields)) {
            return $this->updateById($id, [
                $this->config->softDeleteFields=>1,
            ]);
        }
        // 硬删除
        else {
            return table($this->config->table, $this->config->database)
                ->where([
                    $this->config->primaryKey=>$id,
                ])->delete();
        }
    }

    public function deleteByIds($ids)
    {
        foreach ($ids as $id) {
            $this->deleteById($id);
        }
        return true;
    }

    /************************
     * 配置信息
     ***********************/

    // 获取字段信息(支持group方式)
    public function getFieldsInfo($fields)
    {
        $trueFields = $this->transfer->getTrueFields($fields);
        return array_diff_key($this->config->fields, $trueFields);
    }

    public function getField($field)
    {
        assert_exception(isset($this->config->fields[$field]), 'not has field:' . $field);
        return $this->config->fields[$field];
    }

    // 获取表主键
    public function primaryKey()
    {
        return $this->config->primaryKey;
    }

    // 获取所有字段的过滤器
    public function getFieldsFilters($fields)
    {
        $trueFields = $this->transfer->getTrueFields($fields);
        return array_diff_key($this->config->fieldsFilters, $trueFields);
    }

    public function setDefaultQuery($defaultQuery)
    {
        $this->config->defaultQuery = array_merge($this->config->defaultQuery, $defaultQuery);
    }


    /**
     * 私有函数
     */
    private function getDefaultValues()
    {
        $values = [];
        foreach ($this->config->fields as $field=> $params) {
            if (isset($params['default'])) {
                $values[$field] = $params['default'];
            }
        }
        return $values;
    }

    private function groupByTables($data)
    {
        $dataByTable = [];
        foreach ($data as $field=>$value) {
            $table = $this->config->table;
            if (isset($this->config->fields[$field]['trueField'])) {
                list($table, $field) = explode('.', $this->config->fields[$field]['trueField']);
            }
            $dataByTable[$table][$field] = $value;
        }
        return $dataByTable;
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
            switch ($this->config->fields[$field]['type']) {
                case 'number':
                    $newRow[$field] = (int) $value;
                    break;
                default:
                $newRow[$field] = $value;
            }
        }
        return $newRow;
    }
}


