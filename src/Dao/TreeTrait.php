<?php
namespace Waiterphp\Core\Dao;

trait TreeTrait // 查询为采用dao,直接table，有问题
{
    use DaoTrait;

    protected function setDaoConfig()
    {
        $this->setTreeConfig();
        $this->tree_table = $this->daoConfig->table;
    }

    protected $tree_table = '';    
    protected $tree_idField = '';
    protected $tree_labelField = '';
    protected $tree_topicField = '';
    protected $tree_parentNodeField = '';
    protected $tree_preNodeField = '';
    protected $tree_nextNodeField = '';

    private $tree = [];


    abstract protected function setTreeConfig();

    protected function initTreeFields($nodeId = 'nodeId', $label = 'label', $topicId = 'topicId', $parentId = 'parentId', $preNode = 'preNodeId', $nextNode = 'nextNodeId')
    {
        $this->daoConfig->setPrimaryKey($nodeId);
        $this->daoConfig->setField($label, 'string', '名称');
        $this->daoConfig->setField($topicId, 'number', '所属主题');
        $this->daoConfig->setField($parentId, 'number', '父节点');
        $this->daoConfig->setField($preNode, 'number', '左节点');
        $this->daoConfig->setField($nextNode, 'number', '右节点');
        $this->tree_idField = $nodeId;
        $this->tree_labelField = $label;
        $this->tree_topicField = $topicId;
        $this->tree_parentNodeField = $parentId;
        $this->tree_preNodeField = $preNode;
        $this->tree_nextNodeField = $nextNode;
    }

    public function getTreeKeys()
    {
        return [
            'nodeId'=>$this->tree_idField,
            'label'=>$this->tree_labelField,
            'topicId'=>$this->tree_topicField,
            'parentId'=>$this->tree_parentNodeField,
            'preNodeId'=>$this->tree_preNodeField,
            'nextNodeId'=>$this->tree_nextNodeField
        ];
    }

    public function getKeyName($key)
    {
        $treeKeys = $this->getTreeKeys();
        return $treeKeys[$key];
    }

    public function getTree($topicId, $nodeId = 0)
    {
        $rootTree = $this->rootTree($topicId);
        return $nodeId == 0 ? $rootTree : $this->findNode($rootTree, $nodeId);
    }

    public function getTrace($topicId, $nodeId)
    {
        $rootTree = $this->rootTree($topicId);
        return $this->findNodeTrace($rootTree, $nodeId);
    }

    public function getNodes($topicId, $nodeId)
    {
        $nodes = $this->where([
            $this->tree_topicField=>$topicId,
            $this->tree_parentNodeField=>$nodeId
        ])->getList();
        return $nodes;
    }

    public function treeIds($topicId, $nodeId) {
        $treeIds[] = $nodeId;
        $tree = $this->getTree($topicId, $nodeId);
        if (isset($tree['children'])) {
            $treeIds = array_merge($treeIds, $this->extractNodeIds($tree['children']));
        }
        return $treeIds;
    }

    public function addNode($topicId, $label, $moveToId = 0, $moveType = 'after', $extends = [])
    {
        $_this = $this;
        $nodeId = \Waiterphp\Core\DB\Database::transaction(function() use ($_this, $topicId, $label, $moveToId, $moveType, $extends) {
            $nodeId = $_this->insert(array_merge($extends, [
                $this->tree_topicField=>$topicId,
                $this->tree_labelField=>$label
            ]));
            if ($moveToId != 0) {
                $_this->pushNode($topicId, $nodeId, $moveToId, $moveType);
            }
            $_this->clearCache($topicId);
            return $nodeId;
        });
        return $nodeId;
    }

    public function changeNodeLabel($topicId, $nodeId, $label)
    {
        $this->clearCache($topicId);
        return $this->updateById($nodeId, [
            $this->tree_labelField=>$label,
        ]);
    }

    public function deleteNode($topicId, $nodeId)
    {
        $_this = $this;
        \Waiterphp\Core\DB\Database::transaction(function() use ($_this, $nodeId) {
            $_this->popNode($topicId, $nodeId);
            $_this->deleteById($nodeId);
            $_this->clearCache($topicId);
        });
        return true;
    }

    public function changeNodePosition($topicId, $nodeId, $moveToId, $moveType)
    {
        $_this = $this;
        \Waiterphp\Core\DB\Database::transaction(function() use ($_this, $topicId, $nodeId, $moveToId, $moveType) {
            $_this->popNode($topicId, $nodeId);
            $_this->pushNode($topicId, $nodeId, $moveToId, $moveType);
            $_this->clearCache($topicId);
        });
        return true;
    }

    private function moveToIds($topicId, $moveToId, $moveType)
    {
        $moveInfo = $this->infoById($moveToId);
        if ($moveType == 'before') {
            return [$moveInfo[$this->tree_parentNodeField], $moveInfo[$this->tree_preNodeField], $moveToId];
        } else if ($moveType == 'after') {
            return [$moveInfo[$this->tree_parentNodeField],  $moveToId, $moveInfo[$this->tree_nextNodeField]];
        } else if ($moveType == 'inner'){
            $lastNodes = $this->where([
                $this->tree_topicField=>$topicId,
                $this->tree_parentNodeField=>$moveToId,
                $this->tree_nextNodeField=>0
            ])->getList();
            $lastNodeId = $lastNodes[0][$this->tree_idField];
            return empty($lastNodeId) ? [$moveToId, 0, 0] : [$moveToId, $lastNodeId, 0];
        }
        throw new \Exception('tree error');
    }

    private function pushNode($topicId, $nodeId, $moveToId, $moveType)
    {
        list($parentId, $preNodeId, $nextNodeId) = $this->moveToIds($topicId, $moveToId, $moveType);
        if (!empty($preNodeId)) {
            $this->updateById($preNodeId, [$this->tree_nextNodeField=>$nodeId]);
        }
        if (!empty($nextNodeId)) {
            $this->updateById($nextNodeId, [$this->tree_preNodeField=>$nodeId]);
        }
        $this->updateById($nodeId, [
            $this->tree_parentNodeField=>$parentId,
            $this->tree_preNodeField =>$preNodeId,
            $this->tree_nextNodeField=>$nextNodeId
        ]);
    }

    private function popNode($topicId, $nodeId)
    {
        $info = $this->infoById($nodeId);
        if ($info[$this->tree_preNodeField] > 0) {
            $this->updateById($info[$this->tree_preNodeField], [
                $this->tree_nextNodeField=>$info[$this->tree_nextNodeField]
            ]);
        }
        if ($info[$this->tree_nextNodeField] > 0) {
            $this->updateById($info[$this->tree_nextNodeField], [
                $this->tree_preNodeField=>$info[$this->tree_preNodeField]
            ]);
        }
//        table($this->tree_table)->where([$this->tree_idField=>$nodeId))->update([$this->tree_parentNodeField=>0, $this->tree_preNodeField=>0, $this->tree_nextNodeField=>0));
    }

    private function findNodeTrace($tree, $nodeId, $fatherTrace = [])
    {
        foreach ($tree as $node) {
            $currentNode = [[$this->tree_idField=>$node[$this->tree_idField], $this->tree_labelField=>$node[$this->tree_labelField]]];
            if ($node[$this->tree_idField] == $nodeId) {
                return array_merge($fatherTrace, $currentNode);
            }
            if (isset($node['children'])) {
                $childTrace = $this->findNodeTrace($node['children'], $nodeId, array_merge($fatherTrace, $currentNode));
                if (!empty($childTrace)) {
                    return $childTrace;
                }
            }
        }
        return [];
    }

    private function findNode($tree, $nodeId)
    {
        foreach ($tree as $node) {
            if ($node[$this->tree_idField] == $nodeId) {
                return $node;
            }
            if (isset($node['children'])) {
                $childNode = $this->findNode($node['children'], $nodeId);
                if (!empty($childNode)) {
                    return $childNode;
                }
            }
        }
        return [];
    }

    private function extractNodeIds($tree)
    {
        $ids = [];
        foreach ($tree as $node) {
            $ids[] = $node[$this->tree_idField];
            if (isset($node['children'])) {
                $ids = array_merge($ids, $this->extractNodeIds($node['children']));
            }
        }
        return $ids;
    }

    private function rootTree($topicId)
    {
        if (!isset($this->tree[$topicId])) {
            $nodes = $this->where([
                $this->tree_topicField=>$topicId
            ])->getList();
            $this->tree[$topicId] = $this->makeTree($nodes);
        }
        return $this->tree[$topicId];
    }

    private function makeTree($nodes, $parentId = 0)
    {
        // 组装数据
        $treeMap = [];
        $treeOrders = [];
        foreach ($nodes as $node) {            
            if ($node[$this->tree_parentNodeField] == $parentId) {
                $treeNode = [
                    $this->tree_idField=>$node[$this->tree_idField],
                    $this->tree_labelField=>$node[$this->tree_labelField],
                ];
                $children = $this->makeTree($nodes, $node[$this->tree_idField]);
                if (!empty($children)) {
                    $treeNode['children'] = $children;
                }
                $treeMap[$node[$this->tree_idField]] = $treeNode;
                $treeOrders[(int)$node[$this->tree_preNodeField]] = (int)$node[$this->tree_idField]; // 记录顺序
            }
        }

        // 无子节点
        if (empty($treeMap)) {
            return [];
        }
        // 调整顺序
        $orderedTree = [];
        $nextNodeId = 0;
        do {
            if (!isset($treeOrders[$nextNodeId])) {
                break;
            }
            $nodeId = $treeOrders[$nextNodeId];
            $orderedTree[] = $treeMap[$nodeId];
            $nextNodeId = $nodeId;
        } while($nextNodeId > 0);
        return $orderedTree;
    }

    private function clearCache($topicId)
    {
        unset($this->tree[$topicId]);
    }
}