<?php

use Amiss\Sql\TableBuilder;

abstract class CustomTestCase extends PHPUnit_Framework_TestCase
{
    protected static $parentSetupCalled = false;
    protected static $parentTearDownCalled = false;
    
    public static function setUpBeforeClass()
    {
        self::$parentSetupCalled = false;
        self::$parentTearDownCalled = false;
    }
    
    public function setUp()
    {
        parent::setUp();
        self::$parentSetupCalled = true;
    }
    
    public function tearDown()
    {
        parent::setUp();
        self::$parentTearDownCalled = true;
    }
    
    public static function tearDownAfterClass()
    {
        if (!self::$parentSetupCalled)
            throw new \RuntimeException("Your test case ".get_called_class()." did not call parent::setup()");
        if (!self::$parentTearDownCalled)
            throw new \RuntimeException("Your test case ".get_called_class()." did not call parent::tearDown()");
    }
    
    protected function callProtected($class, $name)
    {
        $ref = new ReflectionClass($class);
        $method = $ref->getMethod($name);
        $method->setAccessible(true);
        
        if ($method->isStatic()) $class = null;
        
        return $method->invokeArgs($class, array_slice(func_get_args(), 2));
    }
    
    protected function getProtected($class, $name)
    {
        $ref = new ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($class);
    }
    
    protected function setProtected($class, $name, $value)
    {
        $ref = new ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->setValue($class, $value);
    }
    
    public function matchesLoose($string)
    {
        return new \LooseStringMatch($string);
    }
    
    public function assertLoose($expected, $value, $message=null)
    {
        $constraint = new \LooseStringMatch($expected);
        
        if (!$message) {
            $message = "Failed asserting that value \"$value\" matches loose string \"$expected\"";
        }
        
        self::assertThat($value, $constraint, $message);
    }
    
    public function createRecordMemoryDb($class)
    {
        $tb = new TableBuilder($this->manager, $class);

        if ($class instanceof \Amiss\Active\Record)
            forward_static_call(array($class, 'setManager'), $this->manager);
        
        $tb->createTable();
    }
}

class DataTestCase extends CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
    }
    
    public function getConnector()
    {
        return new \Amiss\Sql\Connector('sqlite::memory:', null, null, array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
    }
    
    public function getEngine()
    {
        return 'sqlite';
    }
    
    public function readSqlFile($name)
    {
        $name = strtr($name, [
            '{engine}'=>$this->getEngine(),
        ]);
        return file_get_contents($name);
    }
}

class SqliteDataTestCase extends DataTestCase
{
    /**
     * @var Amiss\Sql\Manager
     */
    public $manager;
    
    public function getMapper()
    {
        $mapper = new \Amiss\Mapper\Note();
        $mapper->addTypeSet(new \Amiss\Sql\TypeSet());
        $mapper->objectNamespace = 'Amiss\Demo';
        return $mapper;
    }
    
    public function getManager()
    {
        return new \Amiss\Sql\Manager($this->db, $this->mapper);
    }
    
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        
        $this->db = $this->getConnector();
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/schema.{engine}.sql'));
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/testdata.{engine}.sql'));
        
        $this->mapper = $this->getMapper();
        $this->manager = $this->getManager();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }
}

class ActiveRecordDataTestCase extends SqliteDataTestCase
{
    public function getMapper()
    {
        $mapper = parent::getMapper();
        $mapper->objectNamespace = 'Amiss\Demo\Active';
        return $mapper;
    }
}

class LooseStringMatch extends PHPUnit_Framework_Constraint
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @param string $pattern
     */
    public function __construct($string)
    {
        $this->string = $string;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns TRUE if the
     * constraint is met, FALSE otherwise.
     *
     * @param mixed $other Value or object to evaluate.
     * @return bool
     */
    public function evaluate($other, $description = '', $returnResult = FALSE)
    {
        $result = false;
        if ($this->string) {
            $pattern = '/'.preg_replace('/\s+/', '\s*', preg_quote($this->string, '/')).'/ix';
            $result = preg_match($pattern, $other) > 0;
        }
        if (!$returnResult) {
            if (!$result) $this->fail($other, $description);
        }
        else
            return $result;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return sprintf(
          'matches loose string "%s"',

          $this->string
        );
    }
}
