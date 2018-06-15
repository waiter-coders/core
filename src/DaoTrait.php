<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/14
 * Time: 18:29
 */

namespace Waiterphp\Core;


trait DaoTrait
{
    /**
     * 对外访问接口
     */
    protected $queryTable = '';
    protected $daoConfig = '';
    private $tableFields = array();

    public function __construct(DaoConfig $daoConfig = null)
    {
        // 没有config的则通过load加载子类初始化
        $this->daoConfig = $daoConfig;
        if (empty($this->daoConfig)) {
            $this->daoConfig = new DaoConfig();
            $this->load();
        }
        // 测试配置文件是否可用
        if (!$this->daoConfig->canWork()) {
            throw new Exception('dao config not enough');
        }
        // 初始化dao类变量
        $this->queryTable = table($this->daoConfig->table, $this->daoConfig->database);
        return true;
    }

    protected function load()
    {
        return false;
    }

    /****************************
     * 虚拟表结构相关接口
     ****************************/

    // 获取表字段
    public function getAllFields($hasInfo = false)
    {
        return $hasInfo ? $this->daoConfig->fields : array_keys($this->daoConfig->fields);
    }

    public function getBaseFields($hasInfo = false)
    {
        $reverseHidden = array_flip($this->daoConfig->detailFields);
        $baseFields = array_diff_key($this->daoConfig->fields, $reverseHidden);
        return $hasInfo ? $baseFields : array_keys($baseFields);
    }

    public function isReadonly($field)
    {
        return isset($this->daoConfig->readonlyFields[$field]);
    }

    public function getDetailFields($hasInfo = false)
    {
        $reverseHidden = array_flip($this->daoConfig->detailFields);
        $extractFields = array_intersect_key($this->daoConfig->fields, $reverseHidden);
        return $hasInfo ? $extractFields : array_keys($extractFields);
    }

    // 获取表主键
    public function primaryKey()
    {
        return $this->daoConfig->primaryKey;
    }

    // 获取所有字段的过滤器
    public function getFilters()
    {
        return $this->daoConfig->filters;
    }


    /*************************
     * 设置查询字段
     *************************/

    // 多次存入，一次取出
    public function with($args = null)
    {
        $args = is_array($args) ? $args : func_get_args();
        if (empty($args)) {
            return $this;
        }
        $fields = array();
        $tables = array_unique(explode(',', implode(',', $args)));
        foreach ($tables as $table) {
            $fields = array_merge($fields, $this->tableFields($table));
        }
        return $this->field($fields);
    }

    // 获取所有字段
    public function withAll()
    {
        $tables = array_keys($this->daoConfig->joinTables);
        return call_user_func_array(array($this, 'with'), $tables);
    }

    // 获取特定字段
    public function field($args = null)
    {
        static $fields = array();
        $args = is_array($args) ? $args : func_get_args();
        if (!empty($args)) {
            $args = explode(',', implode(',', $args));
            foreach ($args as $field) {
                if (!isset($this->daoConfig->fields[$field])) {
                    throw new Exception('field not exist:'.$field);
                }
                $fields[] = $field;
            }
            return $this;
        } else {
            $tmp = $fields;
            $fields = array();
            return $tmp;
        }
    }


    /**************************
     * 虚拟表数据获取相关接口
     **************************/

    // 查找数据列表
    public function search($offset, $length, $where = array(), $order = '') // 提供单独的子类可覆盖搜索接口
    {
        return $this->getList($offset, $length, $where, $order);
    }

    // 获取大于某主键id值的列表数据
    public function listAfterId($id, $length, $where = array(), $order = '')
    {
        $where[$this->primaryKey() . ' gt'] = $id;
        return $this->getList(0, $length, $where, $order);
    }

    // 获取小于某主键id值的列表数据
    public function listBeforeId($id, $length, $where = array(), $order = '')
    {
        $where[$this->primaryKey() . ' lt'] = $id;
        return $this->getList(0, $length, $where, $order);
    }

    public function paging($pageNum, $pageSize, $where = array(), $order = '')
    {
        $totalNum = $this->queryTable->count($this->daoConfig->primaryKey, $this->covertCondition($where));
        if ($totalNum == 0) {
            return array(
                'list'=>array(),
                'totalNum'=>0,
            );
        }
        $start = ($pageNum - 1) * $pageSize;
        $list = $this->getList($start, $pageSize, $where, $order);
        return array(
            'list'=>$list,
            'totalNum'=>ceil($totalNum / $pageSize),
        );
    }

    // 根据主键id获取单条记录
    public function infoById($id)
    {
        $data = $this->getList(0, 1, array($this->daoConfig->primaryKey=>$id));
        return (!empty($data)) ? $data[0] : array();
    }

    // 根据某个字段的值获取单条记录
    public function infoByField($value, $fieldName)
    {
        $data = $this->getList(0, 1, array($fieldName=>$value));
        return (!empty($data)) ? $data[0] : array();
    }

    public function detail($id)
    {
        $data = $this->withAll()->getList(0, 1, array($this->daoConfig->primaryKey=>$id));
        return (!empty($data)) ? $data[0] : array();
    }

    private function getList($offset, $length, $where = array(), $order = '')
    {
        $where['limit'] = $offset . ',' . $length;
        if (empty($order)) {
            $order = isset($this->daoConfig->defaultQuery['order']) ? $this->daoConfig->defaultQuery['order'] : $this->primaryKey() . ' desc';
        }
        $where['order'] = $order;

        // 查询字段处理
        $fields = $this->tableFields($this->daoConfig->table);
        $fields = array_unique(array_merge($fields, $this->field()));
        $condition = $where;
        $condition['field'] = implode(',', $this->toTrueFields($fields));

        // 表对象处理
        $table = $this->queryTable;
        $selectTables = $this->fieldsTables($fields);
        foreach ($selectTables as $selectTable) {
            if ($selectTable == $this->daoConfig->table) { // join查询跳过主表
                continue;
            }
            if (!isset($this->daoConfig->joinTables[$selectTable])) { // 检查是否存在join table的配置
                throw new Exception('no join table config:'.$selectTable);
            }
            $join = $this->daoConfig->joinTables[$selectTable];
            $tableName = $join['table'] . ' as ' . $selectTable;
            $on = sprintf('%s.%s = %s.%s', $this->daoConfig->table, $join['mainField'], $selectTable, $join['joinField']);
            $table = $table->leftJoin($tableName)->on($on);
        }

        // 查询条件处理
        $condition = $this->covertCondition($condition);
        $condition = array_merge($this->daoConfig->defaultQuery, $condition);
        if (!empty($this->daoConfig->softDeleteField)) {
            $condition[$this->daoConfig->softDeleteField] = 0;
        }

        // 查询
        $data = $table->where($condition)->fetchAll();
        return $data;
        return DaoPipeline::iteration($data, $this->daoConfig, 'toShow');
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
                throw new Exception('name exist');
            }
        }

        foreach ($data as $key=>$value) {
            if (!isset($value[$dataField])) {
                throw new Exception('filed not exist');
            }
            $record = $this->queryTable->getRow(array(
                $joinField=>$value[$dataField],
            ));
            $data[$key] = $value + $record;
        }
        return true;
    }

    /*****************************
     * 数据更新相关接口
     *****************************/

    // 更新信息
    public function update($update)
    {

        $update = DaoPipeline::iteration($update, $this->daoConfig, 'toDb');
        return $this->queryTable->update($update);
    }

    // 根据主键id更新信息
    public function updateById($id, $update)
    {
        $update = DaoPipeline::iteration($update, $this->daoConfig, 'toDb');
        $update = $this->groupByTables($update);
        foreach ($update as $table=>$data) {
            $idField = $this->daoConfig->primaryKey;
            if (isset($this->daoConfig->joinTables[$table])) { // join表转化为真表
                $idField = $this->daoConfig->joinTables[$table]['joinField'];
                $table = $this->daoConfig->joinTables[$table]['table'];
            }
            table($table)->where(array($idField=>$id))->update($data);
        }
        return true;
    }

    // 根据某一字段值更新信息
    public function updateField($id, $key, $value)
    {
        if (!$this->dataIsSafe(array($key=>$value), $message)) {
            throw new Exception($message);
        }
        return $this->updateById($id, array(
            $key=>$value,
        ));
    }

    // 添加新记录
    public function insert($insert)
    {
        $insert = array_merge($this->getDefaultValues(), $insert);
        if (!$this->dataIsSafe($insert, $message)) {
            throw new Exception($message);
        }
        $insert = DaoPipeline::iteration($insert, $this->daoConfig, 'toDb');
        $insert = $this->groupByTables($insert);
        $mainInsert = $insert[$this->daoConfig->table];
        $mainInsert = array_merge($mainInsert, $this->daoConfig->defaultQuery);
        $mainId = $this->queryTable->insert($mainInsert);
        unset($insert[$this->daoConfig->table]);
        foreach ($insert as $table=>$data) {
            if (!isset($this->daoConfig->joinTables[$table])) { // join表转化为真表
                throw new Exception('not set join info'.$table);
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
        $hasId = $this->getRow(array(
            $this->primaryKey=>$id,
        ));
        if ($hasId) {
            return $this->updateById($id, $refresh);
        } else {
            return $this->insert($refresh);
        }
    }

    // 删除新纪录
    public function delete($id)
    {
        // 软删除
        if (!empty($this->daoConfig->softDeleteField)) {
            return $this->updateById($id, array(
                $this->daoConfig->softDeleteField=>1,
            ));
        }
        // 硬删除
        else {
            return $this->queryTable->delete(array(
                $this->daoConfig->primaryKey=>$id,
            ));
        }
    }

    public function setDefaultQuery($condition)
    {
        $this->daoConfig->defaultQuery = $condition;
    }

    private function dataIsSafe(array $values, &$message = '')
    {
        foreach ($values as $field=>$value) {
            // 字段属性检测
//            if (isset($this->get)) disable过滤

            // 过滤器合法检测
            if (isset($this->daoConfig->filters[$field])) {

            }
        }
        return true;
    }



    /**************************************
     * 缓存计划
     **************************************/
    public function cache()
    {

    }

    public function cancelCache()
    {

    }

    /**
     * 私有函数
     */
    private function getDefaultValues()
    {
        $values = array();
        foreach ($this->daoConfig->fields as $field=>$params) {
            if (isset($params['default'])) {
                $values[$field] = $params['default'];
            }
        }
        return $values;
    }

    private function tableFields($table)
    {
        if (empty($this->tableFields)) {
            $mainTable = $this->daoConfig->table;
            foreach ($this->daoConfig->fields as $field=>$params) {
                $tableName = isset($params['table']) ? $params['table'] : $mainTable;
                $this->tableFields[$tableName][] = $field;
            }
        }
        return isset($this->tableFields[$table]) ? $this->tableFields[$table] : array();
    }

    private function fieldsTables(array $fields)
    {
        $tables = array();
        foreach ($fields as $field) {
            if (!isset($this->daoConfig->fields[$field])) {
                throw new Exception('field config not set:'.$field);
            }
            $config = $this->daoConfig->fields[$field];
            if (isset($config['table'])) {
                $tables[$config['table']] = 1;
            }
        }
        return array_keys($tables);
    }

    private function toTrueFields(array $fields)
    {
        $trueFields = array();
        foreach ($fields as $field) {
            $trueField = $this->trueField($field);
            if ($trueField) {
                $trueFields[] = $trueField . ' as ' . $field;
            }
        }
        return $trueFields;
    }

    private function trueField($field)
    {
        $params = $this->daoConfig->fields[$field];
        if (isset($params['isVirtual']) &&  $params['isVirtual'] == true) {
            return null;
        }
        $table = isset($params['table']) ? $params['table'] : $this->daoConfig->table;
        return isset($params['trueField']) ? $params['trueField'] : $table . '.' . $field;
    }

    private function covertCondition($condition)
    {
        $others = array('field'=>'', 'order'=>'', 'group'=>'', 'limit'=>'');
        foreach ($condition as $field=>$params) {
            if (isset($others[$field])) {
                continue;
            }
            list($trueField, $tip) = explode(' ', $field . ' ');
            $trueField = trim($trueField);$tip = trim($tip);
            $trueField = trim($trueField);$tip = trim($tip);
            $trueField = $this->trueField($trueField);
            $trueField = empty($tip) ? $trueField : $trueField . ' ' . $tip;
            unset($condition[$field]);
            $condition[$trueField] = $params;
        }
        $condition = array_merge($this->daoConfig->defaultQuery, $condition);
        return $condition;
    }

    private function groupByTables($data)
    {
        $dataByTable = array();
        foreach ($data as $field=>$value) {
            $table = $this->daoConfig->table;
            if (isset($this->daoConfig->fields[$field]['trueField'])) {
                list($table, $field) = explode('.', $this->daoConfig->fields[$field]['trueField']);
            }
            $dataByTable[$table][$field] = $value;
        }
        return $dataByTable;
    }

    public function iteration($values, $config, $direction)
    {
        // 空值处理
        if (empty($values)) {
            return array();
        }

        // 多条记录处理
        if (isset($values[0]) && is_array($values[0])) {
            foreach ($values as $key=>$value) {
                $values[$key] = $this->iteration($value, $config, $direction);
            }
            return $values;
        }

        // 单条记录处理
        foreach ($values as $field=>$value) {
            $class = $this->check($field, $config->fields[$field]);
            $class->$direction($values, $field);
        }

        // 虚字段处理
        foreach ($config->fields as $field=>$params) {
            if ($params['isVirtual'] == true) {
                $class = $this->check($field, $params);
                $class->$direction($values);
            }
        }

        return $values;
    }

    public function check($value, $filters)
    {
        foreach ($filters as $filter=>$params) {
            $isLegal = DaoFilter::get($filter)->check($value, $params);
            if (!$isLegal) {
                $message = DaoFilter::get($filter)->errorMessage();
                throw new Exception($message);
            }
        }
    }
}