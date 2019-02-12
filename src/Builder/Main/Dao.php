<?php

namespace Waiterphp\Core\Builder\Main;

use Waiterphp\Core\Builder\Base as Base;


class Dao extends Base
{
    public function build($params = [])
    {
        // 检查数据库配置
        $params = $this->formatParams($params);

        // 获取数据表的结构信息
        $tableStruct = $this->fetchTableStruct($params);

        // 尝试生成文件
        $daoMaker = $this->generateMaker();
        $template = __DIR__ . '/template/Dao.php';
        $buildFile = $this->basePath . '/Model/' . ucfirst($params['table']) . '.php';        
        $daoMaker->template($template)->params($tableStruct)->buildToFile($buildFile);
    }

    private function formatParams($params)
    {
        assert_exception(count($params) > 0, 'params empty');

        // 标准化表名
        $format = [];
        $format['table'] = isset($params[0]) ? $params[0] : ''; // 默认第一个参数为数据表
        $format['table'] = isset($params['table']) ? $params['table'] : $format['table'];
        assert_exception(!empty($format['table']), 'table not set');

        // 标准化数据库
        $format['database'] = 'default';

        return $format;
    }

    private function fetchTableStruct($params)
    {
        $table = $params['table'];
        $struct = table($table)->struct();

        $response = [
            'Model'=>ucfirst($params['table']),
            'table'=>$table,
            'primaryKey'=>'',
            'fields'=>[]
        ];

        foreach ($struct as $field) {
            if ($field['keyType'] == 'PRI') {
                $response['primaryKey'] = $field['field'];
                continue;
            }
            $response['fields'][] = [
                'field'=>$field['field'],
                'type'=>$this->formatDaoType($field['type']),
                'name'=>$field['comment']
            ];
        }
        
        return $response;
    }

    private function formatDaoType($dataType)
    {
        $dataType = strtolower($dataType);
        if ($dataType == 'int' || $dataType == 'tinyint') {
            return 'number';
        }
        if ($dataType == 'timestamp' || $dataType == 'datetime') {
            return 'datetime';
        }
        return 'string';
    }
}