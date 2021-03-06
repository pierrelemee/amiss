<?php
namespace Amiss\Ext\NestedSet;

class TreeRelator implements \Amiss\Sql\Relator
{
    public $nestedSetManager;
    
    public function __construct(Manager $nestedSetManager)
    {
        $this->nestedSetManager = $nestedSetManager;
    }
    
    function getRelated($source, $relationName, $criteria=null)
    {
        $treeMeta = $this->nestedSetManager->getTreeMeta($source);
        $meta = $treeMeta->meta;
        
        $relation = $meta->relations[$relationName];
        
        $leftValue = $meta->getValue($source, $treeMeta->leftId);
        $rightValue = $meta->getValue($source, $treeMeta->rightId);
        
        $query = new \Amiss\Sql\Criteria\Select;
        $query->where = "{".$treeMeta->leftId."} > ? AND {".$treeMeta->rightId."} < ?";
        $query->params = array($leftValue, $rightValue);
        
        if ($criteria) {
            if ($criteria->where) {
                list ($cWhere, $cParams) = $criteria->buildClause($meta);
                $query->params = array_merge($cParams, $query->params);
                $query->where .= ' AND ('.$cWhere.')';
            }
            $query->order = $criteria->order;
        }
        
        $children = $this->nestedSetManager->manager->getList($meta->class, $query);
        
        if ($children)
            return $this->buildTree($treeMeta, $source, $children);
    }
    
    private function buildTree($treeMeta, $rootNode, $objects)
    {
        $meta = $treeMeta->meta;
        
        // primary is ensured to have one column in getTreeMeta
        $primaryField = $meta->primary[0];
        
        $rootNodeId = $meta->getValue($rootNode, $primaryField);
        
        $index = (object)array(
            'nodes'=>array($rootNodeId=>$rootNode),
            'children'=>array(),
        );
        
        $parentIndex = array();
        
        foreach ($objects as $node) {
              $id = $meta->getValue($node, $primaryField);
              $parentId = $meta->getValue($node, $treeMeta->parentId);
              $parentIndex[$id] = $parentId;
              
              $index->nodes[$id] = $node;
              if (!isset($index->children[$parentId])) {
                  $index->children[$parentId] = array();
              }
              $index->children[$parentId][] = $node;
        }
        
        foreach ($objects as $node) {
              $id = $meta->getValue($node, $primaryField);
              $parentId = $parentIndex[$id];
              
              if (isset($index->children[$id]))
                  $meta->setValue($node, $treeMeta->treeRel, $index->children[$id]);
              
              $meta->setValue($node, $treeMeta->parentRel, $index->nodes[$parentId]);
        }
        
        return $index->children[$rootNodeId];
    }
}
