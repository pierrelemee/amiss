<?php

namespace Amiss\Relator;

use Amiss\Criteria;

class OneMany
{
	public function get($manager, $type, $source, $relationName)
	{
		if (!$source) return;
		
		$sourceIsArray = is_array($source) || $source instanceof \Traversable;
		if (!$sourceIsArray) $source = array($source);
		
		$class = !is_object($source[0]) ? $source[0] : get_class($source[0]);
		$meta = $manager->getMeta($class);
		if (!isset($meta->relations[$relationName])) {
			throw new Exception("Unknown relation $relationName on $class");
		}
		
		$relation = $meta->relations[$relationName];
		$relatedMeta = $manager->getMeta($relation[$type]);
		
		// prepare the relation's "on" field
		if ('one'==$type) {
			if (!isset($relation['on']))
				throw new Exception("One-to-one relation {$relationName} on class {$class} does not declare 'on' field");
			$on = $relation['on'];
		}
		else { // many
			$on = $meta->primary;
		}
		if (!is_array($on)) $on = array($on=>$on);
		
		// populate the 'on' with necessary data
		$relatedFields = $relatedMeta->getFields();
		foreach ($on as $l=>$r) {
			$on[$l] = $relatedFields[$r];
		}
		
		// find query values in source object(s)
		$resultIndex = array();
		$ids = array();
		foreach ($source as $idx=>$object) {
			$key = array();
			
			foreach ($on as $l=>$r) {
				$lValue = !isset($relation['getter']) ? $object->$l : call_user_func(array($object, $relation['getter']));
				$key[] = $lValue;
				
				if (!isset($ids[$l])) {
					$ids[$l] = array('values'=>array(), 'rField'=>$r, 'param'=>$manager->sanitiseParam($r['name']));
				}
				
				$ids[$l]['values'][$lValue] = true;
			}
			
			$key = !isset($key[1]) ? $key[0] : implode('|', $key);
			
			if (!isset($resultIndex[$key]))
				$resultIndex[$key] = array();
			
			$resultIndex[$key][$idx] = $object;
		}
		
		// build query
		$criteria = new Criteria\Select;
		$where = array();
		foreach ($ids as $l=>$idInfo) {
			$rName = $idInfo['rField']['name'];
			$criteria->params[$rName] = array_keys($idInfo['values']);
			$where[] = '`'.str_replace('`', '', $rName).'` IN(:'.$idInfo['param'].')';
		}
		$criteria->where = implode(' AND ', $where);
		
		$list = $manager->getList($relation[$type], $criteria);
		
		// prepare the result
		$result = null;
		if (!$sourceIsArray) {
			if ($list)
				$result = 'one' == $type ? current($list) : $list;
		}
		else {
			$result = array();
			foreach ($list as $related) {
				$key = array();
				
				foreach ($on as $l=>$r) {
					$name = $r['name'];
					$rValue = !isset($r['getter']) ? $related->$name : call_user_func(array($object, $r['getter']));
					$key[] = $rValue;
				}
				$key = !isset($key[1]) ? $key[0] : implode('|', $key);
				
				foreach ($resultIndex[$key] as $idx=>$lObj) {
					if ('one' == $type) {
						$result[$idx] = $related;
					}
					elseif ('many' == $type) {
						if (!isset($result[$idx])) $result[$idx] = array();
						$result[$idx][] = $related;
					}
				}
			}
		}
		
		return $result;
	}
}
