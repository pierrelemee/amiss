<?php

namespace Amiss\Test\Unit\Active;

class RecordTest extends \CustomTestCase
{
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		$this->db = new \PDO('sqlite::memory:', null, null, array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
		$this->mapper = new \Amiss\Active\Mapper;
		$this->mapper->objectNamespace = 'Amiss\Demo\Active';
		$this->manager = new \Amiss\Manager($this->db, $this->mapper);
	}
	
	/**
	 * @covers Amiss\Active\Record::getMeta
	 * @group active
	 */
	public function testGetMeta()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$meta = TestActiveRecord1::getMeta();
		$this->assertInstanceOf('Amiss\Meta', $meta);
		$this->assertEquals(__NAMESPACE__.'\TestActiveRecord1', $meta->class);
		
		// ensure the instsance is cached
		$this->assertTrue($meta === TestActiveRecord1::getMeta());
	}
		
	/**
	 * @group active
	 */
	public function testMultiConnection()
	{
		\Amiss\Active\Record::setManager($this->manager);
		$manager2 = clone $this->manager;
		$this->assertFalse($this->manager === $manager2);
		OtherConnBase::setManager($manager2);
		
		$c1 = TestOtherConnRecord1::getManager();
		$c2 = TestOtherConnRecord2::getManager();
		$this->assertTrue($c1 === $c2);
		
		$c3 = TestActiveRecord1::getManager();
		$this->assertFalse($c1 === $c3); 
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @group active
	 */
	public function testGetForwarded()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db, $this->mapper));
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
			$this->equalTo('pants=?'), 
			$this->equalTo(1)
		);
		\Amiss\Active\Record::setManager($manager);
		$tar = new TestActiveRecord1;
		TestActiveRecord1::get('pants=?', 1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @group active
	 */
	public function testGetByExplicitPk()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db, $this->mapper));
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
			$this->equalTo('fooBar=?'), 
			$this->equalTo(1)
		);
		TestActiveRecord1::getByPk(1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @covers Amiss\Active\Record::getByPk
	 * @group active
	 */
	public function testGetByImplicitPk()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db, $this->mapper));
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('get')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord2'), 
			$this->equalTo('testActiveRecord2Id=?'), 
			$this->equalTo(1)
		);
		TestActiveRecord2::getByPk(1);
	}
	
	/**
	 * @covers Amiss\Active\Record::__callStatic
	 * @covers Amiss\Active\Record::getByPk
	 * @group active
	 */
	public function testManyImplicitPks()
	{
		$manager = $this->getMock('Amiss\Manager', array('get'), array($this->db, $this->mapper));
		\Amiss\Active\Record::setManager($manager);
		
		TestActiveRecord2::getByPk(1);
		TestActiveRecord3::getByPk(1);
		
		$this->assertEquals(TestActiveRecord2::getMeta()->primary, 'testActiveRecord2Id');
		$this->assertEquals(TestActiveRecord3::getMeta()->primary, 'testActiveRecord3Id');
	}
	
	/**
	 * @covers Amiss\Active\Record::fetchRelated
	 * @group active
	 */
	public function testFetchRelatedSingle()
	{
		$this->mapper->objectNamespace = 'Amiss\Test\Unit\Active';
		
		$manager = $this->getMock('Amiss\Manager', array('getRelated', 'getRelatedList'), array($this->db, $this->mapper));
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('getRelated')->with(
			$this->isInstanceOf(__NAMESPACE__.'\TestRelatedChild'),
			$this->equalTo(__NAMESPACE__.'\TestRelatedParent'), 
			$this->equalTo('parentId')
		)->will($this->returnValue(999));
		
		$manager->expects($this->never())->method('getRelatedList');
		
		$child = new TestRelatedChild;
		$child->childId = 6;
		$child->parentId = 1;
		$result = $child->fetchRelated('parent');
		$this->assertEquals(999, $result);
	}
	
	/**
	 * @covers Amiss\Active\Record::fetchRelated
	 * @group active
	 */
	public function testFetchRelatedList()
	{
		$this->mapper->objectNamespace = 'Amiss\Test\Unit\Active';
		
		$manager = $this->getMock('Amiss\Manager', array('getRelated', 'getRelatedList'), array($this->db, $this->mapper));
		\Amiss\Active\Record::setManager($manager);
		
		$manager->expects($this->once())->method('getRelatedList')->with(
			$this->isInstanceOf(__NAMESPACE__.'\TestRelatedParent'),
			$this->equalTo(__NAMESPACE__.'\TestRelatedChild'), 
			$this->equalTo('parentId')
		)->will($this->returnValue(123));
		$manager->expects($this->never())->method('getRelated');
		
		$parent = new TestRelatedParent;
		$parent->parentId = 2;
		$result = $parent->fetchRelated('children');
		$this->assertEquals(123, $result);
	}
	
	/**
	 * If a record has not been loaded from the database and the class doesn't
	 * define fields, undefined properties should throw
	 * 
	 * @covers Amiss\Active\Record::__get
	 * @group active
	 * @expectedException BadMethodCallException
	 */
	public function testGetUnknownPropertyWhenFieldsUndefinedOnNewObjectReturnsNull()
	{
		TestActiveRecord1::setManager($this->manager);
		$ar = new TestActiveRecord1();
		$a = $ar->thisPropertyShouldNeverExist;
	}
	
	/**
	 * If the class defines its fields, undefined properties should always throw. 
	 * 
	 * @covers Amiss\Active\Record::__get
	 * @group active
	 * @expectedException BadMethodCallException
	 */
	public function testGetUnknownPropertyWhenFieldsDefinedThrowsException()
	{
		TestActiveRecord1::setManager($this->manager);
		$ar = new TestActiveRecord1();
		$value = $ar->thisPropertyShouldNeverExist;
	}
	
	/**
	 * Even if the class doesn't define its fields, undefined properties should throw
	 * if the record has been loaded from the database as we can expect it is fully
	 * populated.
	 * 
	 * @group active
	 * @expectedException BadMethodCallException
	 */
	public function testGetUnknownPropertyWhenFieldsUndefinedAfterRetrievingFromDatabaseThrowsException()
	{
		TestActiveRecord1::setManager($this->manager);
		$this->db->query("CREATE TABLE table_1(fooBar STRING);");
		$this->db->query("INSERT INTO table_1(fooBar) VALUES(123)");
		
		$ar = TestActiveRecord1::get('fooBar=123');
		$value = $ar->thisPropertyShouldNeverExist;
	}
	
	/**
	 * @group active
	 */
	public function testUpdateTable()
	{
		$manager = $this->getMock('Amiss\Manager', array('update'), array($this->db, $this->mapper), 'PHPUnitGotcha_RecordTest_'.__FUNCTION__);
		$manager->expects($this->once())->method('update')->with(
			$this->equalTo(__NAMESPACE__.'\TestActiveRecord1'), 
			$this->equalTo(array('pants'=>1)),
			$this->equalTo(1)
		);
		TestActiveRecord1::setManager($manager);
		TestActiveRecord1::updateTable(array('pants'=>1), '1');
	}
}

class TestActiveRecord1 extends \Amiss\Active\Record
{
	public static $table = 'table_1';
	public static $primary = 'fooBar';
	
	public $fooBar;
}

class TestActiveRecord2 extends \Amiss\Active\Record
{
	public static $table = 'table_2';
	
	public static $fields = array('testActiveRecord2Id');
}

class TestActiveRecord3 extends \Amiss\Active\Record
{
	public static $fields = array('testActiveRecord3Id');
}

abstract class OtherConnBase extends \Amiss\Active\Record {}

class TestOtherConnRecord1 extends OtherConnBase {}

class TestOtherConnRecord2 extends OtherConnBase {}

class TestRelatedParent extends \Amiss\Active\Record
{
	public $parentId;
	public static $relations = array(
		'children'=>array('many'=>'TestRelatedChild', 'on'=>'parentId')
	);
}

class TestRelatedChild extends \Amiss\Active\Record
{
	public $childId;
	public $parentId;
	public static $relations = array(
		'parent'=>array('one'=>'TestRelatedParent', 'on'=>'parentId')
	);
}
