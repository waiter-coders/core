<?php

namespace Waiterphp\Core;


class DaoTransform
{
    private $daoConfig = null;

    public function __construct(DaoConfig $daoConfig)
    {
        $this->daoConfig = $daoConfig;
    }


    public function queryFieldsByGroup($groups = null)
    {

    }

    public function getQueryTables($fields, $where)
    {
        $query = DB::table($this->daoConfig->table, $this->daoConfig->database);
        $whereFields = $this->getWhereFields($where);
        $selectTables = $this->fieldsTables(array_merge($fields, $whereFields));
        foreach ($selectTables as $selectTable) {
            // join查询跳过主表
            if ($selectTable == $this->daoConfig->table) { continue;}
            // 检查是否存在join table的配置
            assertOrException(isset($this->daoConfig->joinTables[$selectTable]), 'no join table config:'.$selectTable);
            $join = $this->daoConfig->joinTables[$selectTable];
            $tableName = $join['table'] . ' as ' . $selectTable;
            $on = sprintf('%s.%s = %s.%s', $this->daoConfig->table, $join['mainField'], $selectTable, $join['joinField']);
            $query = $query->leftJoin($tableName)->on($on);
        }
        return $query;
    }

    public function getWhereFields($where)
    {
        $fields = array();
        $keys = array_keys($where);
        foreach ($keys as $key) {
            $fields[] = explode(' ', $key, 2)[0];
        }
        return $fields;
    }

    public function getQueryWhere($where)
    {
        $where = array_merge($this->daoConfig->defaultQuery, $where);
        if (!empty($this->daoConfig->softDeleteFields)) {
            $where[$this->daoConfig->softDeleteFields] = 0;
        }
        return $where;
    }

    public function getQueryOrder($order = '')
    {
        if (empty($order)) {
            $order = isset($this->daoConfig->defaultQuery['order']) ? $this->daoConfig->defaultQuery['order'] : $this->daoConfig->primaryKey . ' desc';
        }
        return $order;
    }

    public function getTrueFields($fields)
    {
        $trueFields = array();
        $fields = is_string($fields) ? explode(',', str_replace(' ', '', $fields)) : $fields;
        foreach ($fields as $field) {
            if (isset($this->daoConfig->fields[$field])) {
                $trueFields[] = $this->daoConfig->table .'.' . $field;
            } else if (isset($this->daoConfig->fieldsGroups[$field])) {
                $trueFields = array_merge($trueFields, $this->daoConfig->fieldsGroups[$field]);
            } else if ($field == 'main') {
                $trueFields = $this->getTrueFields(array_keys($this->daoConfig->fields));
            } else {
                throw new \Exception('field not exist:' . $field);
            }
        }
        return $trueFields;
    }

    private function tableFields($table)
    {
        if (empty($this->tableFields)) {
            $mainTable = $this->daoConfig->table;
            foreach ($this->daoConfig->fields as $field=> $params) {
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
            if (isset($this->daoConfig->fields[$field]) && isset($this->daoConfig->fields[$field]['table'])) {
                $tables[$this->daoConfig->fields[$field]['table']] = 1;
            }
            if (($pos = strpos($field, '.')) > 0) {
                $tables[substr($field, 0 , $pos)] = 1;
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

    private function covertCondition($condition)
    {
        foreach ($condition as $field=>$params) {
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



    private function trueField($field)
    {
        $params = $this->daoConfig->fields[$field];
        if (isset($params['isVirtual']) &&  $params['isVirtual'] == true) {
            return null;
        }
        $table = isset($params['table']) ? $params['table'] : $this->daoConfig->table;
        return isset($params['trueField']) ? $params['trueField'] : $table . '.' . $field;
    }
}