<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerInsertTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * Ensures the signature for object insertion works
     *   Amiss\Sql\Manager->insert( object $object )
     */
    public function testInsertObject()
    {
        $deps = Test\Factory::managerModelDemo();

        $this->assertEquals(0, $deps->manager->count('Artist', 'slug="insert-test"'));
            
        $artist = new Demo\Artist();
        $artist->artistTypeId = 1;
        $artist->name = 'Insert Test';
        $artist->slug = 'insert-test';
        $deps->manager->insert($artist);
        
        $this->assertGreaterThan(0, $artist->artistId);
        
        $this->assertEquals(1, $deps->manager->count('Artist', 'slug="insert-test"'));
    }

    /**
     * Ensures object insertion works with a complex mapping (Venue
     * defines explicit field mappings)
     */
    public function testInsertObjectWithManualNoteFields()
    {
        $deps = Test\Factory::managerModelDemo();

        $this->assertEquals(0, $deps->manager->count('Venue', 'slug="insert-test"'));
        
        $venue = new Demo\Venue();
        $venue->venueName = 'Insert Test';
        $venue->venueSlug = 'insert-test';
        $venue->venueAddress = 'yep';
        $venue->venueShortAddress = 'yep';
        $deps->manager->insert($venue);
        
        $this->assertGreaterThan(0, $venue->venueId);
        
        $row = $deps->manager->connector->prepare("SELECT * from venue WHERE venueId=?")
            ->execute(array($venue->venueId))
            ->fetch(\PDO::FETCH_ASSOC);

        $this->assertEquals($venue->venueName, $row['name']);
        $this->assertEquals($venue->venueSlug, $row['slug']);
        $this->assertEquals($venue->venueAddress, $row['address']);
        $this->assertEquals($venue->venueShortAddress, $row['shortAddress']);
    }
    
    /**
     * Ensures the signature for table insertion works
     *   Amiss\Sql\Manager->insert( string $table , array $values )
     * 
     * @group acceptance
     * @group manager
     */
    public function testInsertTable()
    {
        $deps = Test\Factory::managerModelDemo();

        $this->assertEquals(0, $deps->manager->count('Artist', 'slug="insert-table-test"'));
        
        $id = $deps->manager->insertTable('Artist', array(
            'name'=>'Insert Table Test',
            'slug'=>'insert-table-test',
            'artistTypeId'=>1,
        ));
        
        $this->assertGreaterThan(0, $id);
        
        $this->assertEquals(1, $deps->manager->count('Artist', 'slug="insert-table-test"'));
    }
}
