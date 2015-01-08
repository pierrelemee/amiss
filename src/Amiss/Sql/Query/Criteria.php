<?php
namespace Amiss\Sql\Query;

use Amiss\Sql;

class Criteria extends Sql\Query
{
    public $table;
    public $where;
    public $params=array();

    // this hack is for the auto relations circular ref hack
    public $stack = [];
    
    public function buildClause($meta)
    {
        $where = $this->where;
        $params = array();
        $namedParams = $this->paramsAreNamed(); 
        
        $fields = null;
        if ($meta) $fields = $meta->getFields();
        
        if (is_array($where)) {
            $wh = array();
            foreach ($where as $k=>$v) {
                if (isset($fields[$k])) {
                    $k = $fields[$k]['name'];
                }
                $wh[] = '`'.str_replace('`', '', $k).'`=:'.$k;
                $params[':'.$k] = $v;
            }
            $where = implode(' AND ', $wh);
            $namedParams = true;
        }
        else {
            if ($fields && strpos($where, '{') !== false) {
                $where = $this->replaceFieldTokens($fields, $where);
            }
        }
        
        if ($namedParams) {
            foreach ($this->params as $k=>$v) {
                // ($k == 0 && $k !== 0) == !is_numeric($k)
                if (($k == 0 && $k !== 0) && $k[0] != ':') {
                    $k = ':'.$k;
                }
                if (is_array($v)) {
                    $inparms = array();
                    $cnt = 0;
                    $v = array_unique($v);
                    foreach ($v as $val) {
                        $inparms[$k.'_'.$cnt++] = $val;
                    }
                    $params = array_merge($params, $inparms);
                    $where = preg_replace("@IN\s*\($k\)@i", "IN(".implode(',', array_keys($inparms)).")", $where);
                }
                else {
                    $params[$k] = $v;
                }
            }
        }
        else {
            $params = $this->params;
        }
        return array($where, $params);
    }
    
    public function paramsAreNamed()
    {
        if (is_array($this->where)) {
            return true;
        }
        foreach ($this->params as $k=>$v) {
            if ($k==0 && $k!==0) {
                return true;
            }
        }
    }
    
    protected function replaceFieldTokens($fields, $clause)
    {
        $tokens = array();
        foreach ($fields as $k=>$v) {
            $tokens['{'.$k.'}'] = '`'.$v['name'].'`';
        }
        $clause = strtr($clause, $tokens);
        
        return $clause;
    }
}
