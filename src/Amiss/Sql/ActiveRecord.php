<?php
namespace Amiss\Sql;

use Amiss\Exception;

abstract class ActiveRecord
{
    private static $services=array();
    private static $meta=array();
    
    /**
     * For testing only
     * @ignore
     */
    public static function _reset()
    {
        self::$services = array();
        self::$meta = array();
    }
    
    protected function beforeInsert() {}
    
    protected function beforeSave() {}
    
    protected function beforeUpdate() {}
    
    protected function beforeDelete() {}
    
    public function save()
    {
        $manager = static::getDependency('manager');
        if ($manager->shouldInsert($this))
            $this->insert();
        else
            $this->update();
    }
    
    public function insert()
    {
        $this->beforeInsert();
        $this->beforeSave();
        return static::getDependency('manager')->insert($this);
    }
    
    public function update()
    {
        $this->beforeUpdate();
        $this->beforeSave();
        return static::getDependency('manager')->update($this);
    }
    
    public function delete()
    {
        $this->beforeDelete();
        return static::getDependency('manager')->delete($this);
    }
    
    /**
     * @return Amiss\Sql\Manager
     */
    public static function getDependency($id, $class=null)
    {
        if (!$class)
            $class = get_called_class();
        
        if (!isset(self::$services[$id][$class])) {
            $parent = get_parent_class($class);
            if ($parent)
                self::$services[$id][$class] = static::getDependency($id, $parent);
        }
        
        if (!isset(self::$services[$id][$class]))
            throw new Exception("No manager defined against $class or any parent thereof");
        
        return self::$services[$id][$class];
    }
    
    public static function setDependency($id, $service)
    {
        $class = get_called_class();
        self::$services[$id][$class] = $service;
    }
    
    public static function getManager($class=null)
    {
        return static::getDependency('manager', $class);
    }
    
    public static function setManager(Manager $manager=null)
    {
        return static::setDependency('manager', $manager);
    }
    
    public static function getMeta($class=null)
    {
        $class = $class ?: get_called_class();
        if (!isset(self::$meta[$class]))
            self::$meta[$class] = static::getDependency('manager')->getMeta($class);
        
        return self::$meta[$class];
    }
    
    public static function updateTable()
    {
        $manager = static::getDependency('manager');
        $meta = static::getMeta();
        
        $args = func_get_args();
        array_unshift($args, $meta->class);
        
        return call_user_func_array(array($manager, 'update'), $args);
    }
    
    /**
     * @ignore
     */
    public function __call($name, $args)
    {
        $manager = static::getDependency('manager');
        
        $exists = null;
        if ($name == 'getRelated' || $name == 'assignRelated') { 
            $exists = true; 
            array_unshift($args, $this);
        }
        
        if ($exists)
            return call_user_func_array(array($manager, $name), $args);
        else
            throw new \BadMethodCallException("Unknown method $name on class ".get_class($this));
    }
    
    /**
     * @ignore
     */
    public static function __callStatic($name, $args)
    {
        $manager = static::getDependency('manager');
        $called = get_called_class();
        
        $exists = null;
        if ($name == 'get'     || $name == 'getByPk'    || 
            $name == 'getById' || $name == 'getList'    || 
            $name == 'count'   || $name == 'deleteById'
        ) { 
            $exists = true; 
            array_unshift($args, $called);
        }
        elseif ($name == 'assignRelated') {
            $exists = true;
        }
        
        if ($exists)
            return call_user_func_array(array($manager, $name), $args);
        else
            throw new \BadMethodCallException("Unknown method $name");
    }
    
    /**
     * @ignore
     */
    public function __get($name)
    {
        $meta = static::getMeta();
        
        $fields = $meta->getFields();
        if (!isset($fields[$name])) {
            throw new \BadMethodCallException("Unknown property $name on class ".get_class($this));
        }
        else {
            // add the property to stop this from being called again
            $this->$name = null;
        }
    }
    
    /**
     * @ignore
     */
    public function __set($name, $value)
    {
        $meta = static::getMeta();
        
        $fields = $meta->getFields();
        if (!isset($fields[$name])) {
            throw new \BadMethodCallException("Unknown property $name on class ".get_class($this));
        }
        else {
            // add the property to stop this from being called again
            $this->$name = $value;
        }
    }
}
