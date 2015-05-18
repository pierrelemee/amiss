<?php
namespace Amiss\Sql\Query;

class Select extends Criteria
{
    public $args=array();
    public $page;
    public $limit;
    public $offset = 0;
    public $fields;
    public $order = array();
    public $forUpdate = false;
    public $follow = true;
    public $with = [];

    public function getLimitOffset()
    {
        if ($this->limit) {
            return [$this->limit, $this->offset];
        } else {
            return [$this->page[1], ($this->page[0] - 1) * $this->page[1]]; 
        }
    }

    public function buildQuery($meta)
    {
        $table = $this->table ?: $meta->table;
        
        list ($where, $params, $properties) = $this->buildClause($meta);
        $order = $this->buildOrder($meta);
        list ($limit, $offset) = $this->getLimitOffset();
        
        $query = "SELECT ".$this->buildFields($meta)." FROM $table "
            .($where  ? "WHERE $where "            : '').' '
            .($order  ? "ORDER BY $order "         : '').' '
            .($limit  ? "LIMIT  ".(int)$limit." "  : '').' '
            .($offset ? "OFFSET ".(int)$offset." " : '').' '

            .($this->forUpdate ? 'FOR UPDATE' : '')
        ;
        
        return array($query, $params, $properties);
    }
    
    public function buildFields($meta, $tablePrefix=null, $fieldAliasPrefix=null)
    {
        $metaFields = $meta ? $meta->getFields() : null;
        
        $fields = $this->fields;
        if (!$fields) {
            $fields = $metaFields ? array_keys($metaFields) : '*';
        }
        
        if (is_array($fields)) {
            $fNames = array();
            foreach ($fields as $f) {
                $name = (isset($metaFields[$f]) ? $metaFields[$f]['name'] : $f);
                if (isset($this->aliases[$name])) {
                    $name = $this->aliases[$name];
                }
                $qname = $name[0] == '`' ? $name : '`'.$name.'`';
                $fName = ($tablePrefix ? $tablePrefix.'.' : '').$qname;
                if ($fieldAliasPrefix) {
                    $fName .= ' as `'.$fieldAliasPrefix.$name.'`';
                }
                $fNames[] = $fName;
            }
            $fields = implode(', ', $fNames);
        }
        
        return $fields;
    }

    // damn, this is pretty much identical to the above.
    public function buildOrder($meta, $tableAlias=null)
    {
        $metaFields = $meta ? $meta->getFields() : null;
        
        $order = $this->order;
        
        if ($order) {
            if (is_array($order)) {
                $oClauses = array();
                foreach ($order as $field=>$dir) {
                    if (!($field == 0 && $field !== 0)) { // is_numeric($field)
                        $field = $dir; $dir = 'asc';
                    }
                    
                    $name = (isset($metaFields[$field]) ? $metaFields[$field]['name'] : $field);
                    if (isset($this->aliases[$name])) {
                        $name = $this->aliases[$name];
                    }
                    $qname = $name[0] == '`' ? $name : '`'.$name.'`';
                    $qname = ($tableAlias ? $tableAlias.'.' : '').$qname;
                    $oClauses[] = $qname.($dir == 'asc' ? '' : ' desc');
                }
                $order = implode(', ', $oClauses);
            }
            else {
                if ($metaFields && strpos($order, '{')!==false) {
                    $order = $this->replaceFieldTokens($metaFields, $order, $tableAlias);
                }
            }
        }
        
        return $order;
    }
}