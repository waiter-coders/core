<?php
namespace Waiterphp\Core\Dao;

use Waiterphp\Core\Dao\Lib\DaoConfig as DaoConfig;
use Waiterphp\Core\Dao\Lib\DaoFilter as DaoFilter;
use Waiterphp\Core\Dao\Lib\DaoTransform as DaoTransform;

trait DaoTrait
{
    protected $daoConfig; // 配置
    private $daoTransform; // 信息转换
    private $daoFilter; // 过滤器
    private $pipelines; // 数据处理通道
    private $tableFields = [];
    private $query = [];
    private $defaultQuery = ['fields'=>'main', 'where'=> [], 'limit'=>'12', 'offset'=>0, 'orderBy'=>''];

    public function __construct()
    {
        $this->daoConfig = new DaoConfig();
        $this->setDaoConfig();
        assert_exception(!empty($this->daoConfig->table), 'not set table');
        assert_exception(!empty($this->daoConfig->primaryKey), 'primary key not set');
        $this->daoTransform = new DaoTransform($this->daoConfig);
        $this->daoFilter = new DaoFilter($this->daoConfig);
    }

    abstract protected function setDaoConfig();


    /************************
     * 配置信息
     ***********************/

    // 获取字段信息(支持group方式)
    public function getFieldsInfo($fields)
    {
        $trueFields = $this->daoTransform->getTrueFields($fields);
        return array_diff_key($this->daoConfig->fields, $trueFields);
    }

    public function getField($field)
    {
        assert_exception(isset($this->daoConfig->fields[$field]), 'not has field:' . $field);
        return $this->daoConfig->fields[$field];
    }

    // 获取表主键
    public function primaryKey()
    {
        return $this->daoConfig->primaryKey;
    }

    // 获取所有字段的过滤器
    public function getFieldsFilters($fields)
    {
        $trueFields = $this->daoTransform->getTrueFields($fields);
        return array_diff_key($this->daoConfig->fieldsFilters, $trueFields);
    }

    public function setDefaultQuery($defaultQuery)
    {
        $this->daoConfig->defaultQuery = array_merge($this->daoConfig->defaultQuery, $defaultQuery);
    }

    /************************
     * 连续查询条件
     ***********************/

    public function select($fields)
    {
        $this->query['fields'] = $fields;
        return $this;
    }

    public function where($where)
    {
        $this->query['where'] = $where;
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

    public function orderBy($orderBy)
    {
        $this->query['$orderBy'] = $orderBy;
        return $this;
    }

    /************************
     * 查询方法
     ***********************/

    // 获取大于某主键id值的列表数据
    public function listAfterId($id)
    {
        $this->query['where'][$this->primaryKey() . ' >'] = $id;
        return $this->currentQuery()->fetchAll();
    }

    // 获取小于某主键id值的列表数据
    public function listBeforeId($id)
    {
        $this->query['where'][$this->primaryKey() . ' <'] = $id;
        return $this->currentQuery()->fetchAll();
    }

    // 根据主键id获取单条记录
    public function infoById($id)
    {
        $this->query['where'][$this->primaryKey()] = $id;
        return $this->currentQuery()->fetchRow();
    }

    // 根据某个字段的值获取单条记录
    public function infoByField($field, $value)
    {
        $this->query['where'][$field] = $value;
        return $this->currentQuery()->fetchRow();
    }

    public function appendInfoByIds($fields, array &$data, $dataField = null, $joinField = null)
    {
        if (empty($dataField)) {
            $dataField = $this->daoConfig->primaryKey;
        }
        if (empty($joinField)) {
            $joinField = $this->daoConfig->primaryKey;
        }

        // 检查是否有字段重名
        $fieldsArray = explode(',', $fields);
        foreach ($fieldsArray as $field) {
            if (isset($data[0][$field])) {
                throw new \Exception('name exist');
            }
        }

        $query = table($this->daoConfig->table, $this->daoConfig->database);
        foreach ($data as $key=>$value) {
            if (!isset($value[$dataField])) {
                throw new \Exception('filed not exist');
            }
            $record = $query->getRow([
                $joinField=>$value[$dataField],
            ]);
            $data[$key] = $value + $record;
        }
        return true;
    }

    public function count()
    {
        return $this->currentQuery()->count();
    }

    public function getList()
    {
        $data = $this->currentQuery()->fetchAll();
        return $data;
    }

    public function currentQuery()
    {
        // 获取查询信息
        $query = array_merge($this->defaultQuery, $this->query); $this->query = [];

        // dao转化为db数据
        $select = $this->daoTransform->getTrueFields($query['fields']);
        $where = $this->daoTransform->getQueryWhere($query['where']);
        $queryTables = $this->daoTransform->getQueryTables($select, $where);
        $orderBy = $this->daoTransform->getQueryOrder($query['orderBy']);
        return $queryTables->select($select)->where($where)->orderBy($orderBy)->limit($query['limit'])->offset($query['offset']);
    }

    /*****************************
     * 数据操作方法
     *****************************/

    // 更新信息
    public function update($update)
    {

        $update = DaoPipeline::iteration($update, $this->daoConfig, 'toDb');
        $query = table($this->daoConfig->table, $this->daoConfig->database);
        return $query->update($update);
    }

    // 根据主键id更新信息
    public function updateById($id, $update)
    {
        $update = $this->daoFilter->input($update);
        $update = $this->groupByTables($update);
        foreach ($update as $table=>$data) {
            $idField = $this->daoConfig->primaryKey;
            if (isset($this->daoConfig->joinTables[$table])) { // join表转化为真表
                $idField = $this->daoConfig->joinTables[$table]['joinField'];
                $table = $this->daoConfig->joinTables[$table]['table'];
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
        $insert = $this->daoFilter->input($insert);
        $insert = $this->groupByTables($insert);
        $mainInsert = $insert[$this->daoConfig->table];
        $query = table($this->daoConfig->table, $this->daoConfig->database);
        $mainId = $query->insert($mainInsert);
        unset($insert[$this->daoConfig->table]);
        foreach ($insert as $table=>$data) {
            if (!isset($this->daoConfig->joinTables[$table])) { // join表转化为真表
                throw new \Exception('not set join info'.$table);
            }
            $join = $this->daoConfig->joinTables[$table];
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
        if (!empty($this->daoConfig->softDeleteFields)) {
            return $this->updateById($id, [
                $this->daoConfig->softDeleteFields=>1,
            ]);
        }
        // 硬删除
        else {
            return table($this->daoConfig->table, $this->daoConfig->database)
                ->where([
                    $this->daoConfig->primaryKey=>$id,
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


    /**
     * 私有函数
     */
    private function getDefaultValues()
    {
        $values = [];
        foreach ($this->daoConfig->fields as $field=> $params) {
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
            $table = $this->daoConfig->table;
            if (isset($this->daoConfig->fields[$field]['trueField'])) {
                list($table, $field) = explode('.', $this->daoConfig->fields[$field]['trueField']);
            }
            $dataByTable[$table][$field] = $value;
        }
        return $dataByTable;
    }
}


