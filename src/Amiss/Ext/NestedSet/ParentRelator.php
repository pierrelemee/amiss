<?php
namespace Amiss\Ext\NestedSet;

class ParentRelator implements \Amiss\Sql\Relator
{
    public $nestedSetManager;
    
    public function __construct(Manager $nestedSetManager)
    {
        $this->nestedSetManager = $nestedSetManager;
    }
    
    function getRelated($source, $relationName, $criteria=null)
    {
        if ($criteria)
            throw new \InvalidArgumentException("Can't use criteria with parent relator");
        
        $treeMeta = $this->nestedSetManager->getTreeMeta($source);
        
        $parentIdValue = $treeMeta->meta->getValue($source, $treeMeta->parentId);
        if ($parentIdValue)
            return $this->nestedSetManager->manager->getById($treeMeta->meta->class, $parentIdValue);
    }
}
