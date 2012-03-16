<?php

namespace Amiss\Active;

use	Amiss\Connector,
	Amiss\Exception
;

abstract class Record
{
	private static $managers=array();
	private static $meta=array();
	
	// for testing only
	public static function _reset()
	{
		self::$managers = array();
		self::$meta = array();
	}
	
	protected function beforeInsert() {}
	
	protected function beforeSave() {}
	
	protected function beforeUpdate() {}
	
	protected function beforeDelete() {}
	
	public function save()
	{
		$manager = static::getManager();
		if ($manager->shouldInsert($this))
			$this->insert();
		else
			$this->update();
	}
	
	public function insert()
	{
		$this->beforeInsert();
		$this->beforeSave();
		$return = static::getManager()->insert($this);
	}
	
	public function update()
	{
		$this->beforeUpdate();
		$this->beforeSave();
		$return = static::getManager()->update($this);
	}
	
	public function delete()
	{
		$this->beforeDelete();
		static::getManager()->delete($this);
	}
	
	/**
	 * @return Amiss\Manager
	 */
	public static function getManager($class=null)
	{
		if (!$class)
			$class = get_called_class();
		
		if (!isset(self::$managers[$class])) {
			$parent = get_parent_class($class);
			if ($parent)
				self::$managers[$class] = static::getManager($parent);
		}
		
		if (!isset(self::$managers[$class]))
			throw new Exception("No manager defined against $class or any parent thereof");
		
		return self::$managers[$class];
	}
	
	public static function setManager($manager)
	{
		$class = get_called_class();
		self::$managers[$class] = $manager;
	}

	public static function getMeta()
	{
		$called = get_called_class();
		if (!isset(self::$meta[$called]))
			self::$meta[$called] = static::getManager()->getMeta($called);
		
		return self::$meta[$called];
	}
	
	public static function updateTable()
	{
		$manager = static::getManager();
		$meta = static::getMeta();
		
		$args = func_get_args();
		array_unshift($args, $meta->class);
		
		return call_user_func_array(array($manager, 'update'), $args);
	}

	public function __call($name, $args)
	{
		$manager = static::getManager();
		
		$exists = null;
		if ($name == 'getRelated' || $name == 'assignRelated') { 
			$exists = true; 
			array_unshift($args, $this);
		}
		
		if ($exists)
			return call_user_func_array(array($manager, $name), $args);
		else
			throw new \BadMethodCallException("Unknown method $name");
	}
	
	public static function __callStatic($name, $args)
	{
		$manager = static::getManager();
		$called = get_called_class();
		
		$exists = null;
		if ($name == 'get' || $name == 'getByPk' || $name == 'getList' || $name == 'count') { 
			$exists = true; 
			array_unshift($args, $called);
		}
		
		if ($exists)
			return call_user_func_array(array($manager, $name), $args);
		else
			throw new \BadMethodCallException("Unknown method $name");
	}
	
	public function __get($name)
	{
		$meta = static::getManager()->getMeta(get_called_class());
		
		$fields = $meta->getFields();
		if (!isset($fields[$name])) {
			throw new \BadMethodCallException("Unknown property $name");
		}
		else {
			// add the property to stop this from being called again
			$this->$name = null;
		}
	}
}
