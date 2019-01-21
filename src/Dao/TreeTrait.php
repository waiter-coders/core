<?php
namespace Waiterphp\Core\Dao;

trait TreeTrait
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
    protected $tree_parentNodeField = '';
    protected $tree_preNodeField = '';
    protected $tree_nextNodeField = '';

    private $tree = array();


    abstract protected function setTreeConfig();

    protected function initTreeFields($nodeId = 'nodeId', $label = 'label', $parentId = 'parentId', $preNode = 'preNodeId', $nextNode = 'nextNodeId')
    {
        $this->daoConfig->setPrimaryKey($nodeId);
        $this->daoConfig->setField($label, 'string', '名称');
        $this->daoConfig->setField($parentId, 'number', '父节点');
        $this->daoConfig->setField($preNode, 'number', '左节点');
        $this->daoConfig->setField($nextNode, 'number', '右节点');
        $this->tree_idField = $nodeId;
        $this->tree_labelField = $label;
        $this->tree_parentNodeField = $parentId;
        $this->tree_preNodeField = $preNode;
        $this->tree_nextNodeField = $nextNode;
    }

    public function getTreeKeys()
    {
        return array(
            'nodeId'=>$this->tree_idField,
            'label'=>$this->tree_labelField,
            'parentId'=>$this->tree_parentNodeField,
            'preNodeId'=>$this->tree_preNodeField,
            'nextNodeId'=>$this->tree_nextNodeField
        );
    }

    public function getTree($nodeId = 0)
    {
        $rootTree = $this->rootTree();
        return $nodeId == 0 ? $rootTree : $this->findNode($rootTree, $nodeId);
    }

    public function getTrace($nodeId)
    {
        $rootTree = $this->rootTree();
        return $this->findNodeTrace($rootTree, $nodeId);
    }

    public function getNodes($nodeId)
    {
        $nodes = table($this->tree_table)->where(array($this->tree_parentNodeField=>$nodeId))->fetchAll();
        return $nodes;
    }

    public function treeIds($nodeId) {
        $treeIds[] = $nodeId;
        $tree = $this->getTree($nodeId);
        if (isset($tree['children'])) {
            $treeIds = array_merge($treeIds, $this->extractNodeIds($tree['children']));
        }
        return $treeIds;
    }

    public function addNode($label, $moveToId = 0, $moveType = 'after', $extends = array())
    {
        $_this = $this;
        $nodeId = \Waiterphp\Core\Lib\DB::transaction(function() use ($_this, $label, $moveToId, $moveType, $extends) {
            $nodeId = table($_this->tree_table)->insert(array_merge($extends, array($this->tree_labelField=>$label)));
            if ($moveToId != 0) {
                $_this->pushNode($nodeId, $moveToId, $moveType);
            }
            $_this->clearCache();
            return $nodeId;
        });
        return $nodeId;
    }

    public function changeNodeLabel($nodeId, $label)
    {
        $this->clearCache();
        return table($this->tree_table)->where(array($this->tree_idField=>$nodeId))->update(array(
            $this->tree_labelField=>$label,
        ));
    }

    public function deleteNode($nodeId)
    {
        $_this = $this;
        \Waiterphp\Core\Lib\DB::transaction(function() use ($_this, $nodeId) {
            $_this->popNode($nodeId);
            table($_this->tree_table)->where(array($this->tree_idField=>$nodeId))->delete();
            $_this->clearCache();
        });
        return true;
    }

    public function changeNodePosition($nodeId, $moveToId, $moveType)
    {
        $_this = $this;
        \Waiterphp\Core\Lib\DB::transaction(function() use ($_this, $nodeId, $moveToId, $moveType) {
            $_this->popNode($nodeId);
            $_this->pushNode($nodeId, $moveToId, $moveType);
            $_this->clearCache();
        });
        return true;
    }

    private function moveToIds($moveToId, $moveType)
    {
        $moveInfo = $this->infoById($moveToId);
        if ($moveType == 'before') {
            return array($moveInfo[$this->tree_parentNodeField], $moveInfo[$this->tree_preNodeField], $moveToId);
        } else if ($moveType == 'after') {
            return array($moveInfo[$this->tree_parentNodeField],  $moveToId, $moveInfo[$this->tree_nextNodeField]);
        } else if ($moveType == 'inner'){
            $lastNodeId = table($this->tree_table)->select($this->tree_idField)->where(array(
                $this->tree_parentNodeField=>$moveToId,
                $this->tree_nextNodeField=>0
            ))->fetchColumn();
            return empty($lastNodeId) ? array($moveToId, 0, 0) : array($moveToId, $lastNodeId, 0);
        }
        throw new \Exception('tree error');
    }

    private function pushNode($nodeId, $moveToId, $moveType)
    {
        list($parentId, $preNodeId, $nextNodeId) = $this->moveToIds($moveToId, $moveType);
        if (!empty($preNodeId)) {
            table($this->tree_table)->where(array($this->tree_idField=>$preNodeId))->update(array($this->tree_nextNodeField=>$nodeId));
        }
        if (!empty($nextNodeId)) {
            table($this->tree_table)->where(array($this->tree_idField=>$nextNodeId))->update(array($this->tree_preNodeField=>$nodeId));
        }
        table($this->tree_table)->where(array($this->tree_idField=>$nodeId))->update(array(
            $this->tree_parentNodeField=>$parentId, $this->tree_preNodeField =>$preNodeId, $this->tree_nextNodeField=>$nextNodeId));
    }

    private function popNode($nodeId)
    {
        $info = table($this->tree_table)->where(array($this->tree_idField=>$nodeId))->fetchRow();
        if ($info[$this->tree_preNodeField] > 0) {
            table($this->tree_table)->where(array($this->tree_idField=>$info[$this->tree_preNodeField]))->update(array($this->tree_nextNodeField=>$info[$this->tree_nextNodeField]));
        }
        if ($info[$this->tree_nextNodeField] > 0) {
            table($this->tree_table)->where(array($this->tree_idField=>$info[$this->tree_nextNodeField]))->update(array($this->tree_preNodeField=>$info[$this->tree_preNodeField]));
        }
//        table($this->tree_table)->where(array($this->tree_idField=>$nodeId))->update(array($this->tree_parentNodeField=>0, $this->tree_preNodeField=>0, $this->tree_nextNodeField=>0));
    }

    private function findNodeTrace($tree, $nodeId, $fatherTrace = array())
    {
        foreach ($tree as $node) {
            $currentNode = array(array($this->tree_idField=>$node[$this->tree_idField], $this->tree_labelField=>$node[$this->tree_labelField]));
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
        return array();
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
        return array();
    }

    private function extractNodeIds($tree)
    {
        $ids = array();
        foreach ($tree as $node) {
            $ids[] = $node[$this->tree_idField];
            if (isset($node['children'])) {
                $ids = array_merge($ids, $this->extractNodeIds($node['children']));
            }
        }
        return $ids;
    }

    private function rootTree()
    {
        if (empty($this->tree)) {
            $nodes = table($this->tree_table)->fetchAll();
            $this->tree = $this->makeTree($nodes);
        }
        return $this->tree;
    }

    private function makeTree($nodes, $parentId = 0)
    {
        // 组装数据
        $treeMap = array();
        $treeOrders = array();
        foreach ($nodes as $node) {
            if ($node[$this->tree_parentNodeField] == $parentId) {
                $treeNode = array(
                    $this->tree_idField=>$node[$this->tree_idField],
                    $this->tree_labelField=>$node[$this->tree_labelField],
                );
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
            return array();
        }
        // 调整顺序
        $orderedTree = array();
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

    private function clearCache()
    {
        $this->tree = array();
    }
}