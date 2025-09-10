<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Core\Collections\Collection;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Simple test class to use in collection tests
 */
class TestItem
{
    public $id;
    public $name;

    public function __construct($id, $name = null)
    {
        $this->id = $id;
        $this->name = $name ?? "Item {$id}";
    }
}

/**
 * Test class for Collection
 */
class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new Collection(TestItem::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->collection = null;
    }

    /**
     * Test collection constructor with empty items
     */
    public function testConstructorEmpty(): void
    {
        $collection = new Collection(TestItem::class);
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(0, $collection->count());
    }

    /**
     * Test collection constructor with initial items
     */
    public function testConstructorWithItems(): void
    {
        // Create test items directly
        $item1 = new TestItem(1, 'Item One');
        $item2 = new TestItem(2, 'Item Two');
        $item3 = new TestItem(3, 'Item Three');
        
        // Create a collection and add the items
        $collection = new Collection(TestItem::class);
        $collection->addObject($item1);
        $collection->addObject($item2);
        $collection->addObject($item3);
        
        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertEquals(3, $collection->count());
        
        $firstItem = $collection->first();
        $this->assertInstanceOf(TestItem::class, $firstItem);
        $this->assertEquals(1, $firstItem->id);
        $this->assertEquals('Item One', $firstItem->name);
    }

    /**
     * Test addObject method
     */
    public function testAddObject(): void
    {
        $item = new TestItem(1, 'Test Item');
        $result = $this->collection->addObject($item);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->collection->count());
        $this->assertSame($item, $this->collection->first());
        
        // Test adding an object of wrong type
        $wrongItem = new \stdClass();
        $result = $this->collection->addObject($wrongItem);
        
        $this->assertFalse($result);
        $this->assertEquals(1, $this->collection->count());
    }

    /**
     * Test all method
     */
    public function testAll(): void
    {
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two'),
            new TestItem(3, 'Item Three')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        $allItems = $this->collection->all();
        
        $this->assertIsArray($allItems);
        $this->assertCount(3, $allItems);
        $this->assertSame($items, $allItems);
    }

    /**
     * Test clear method
     */
    public function testClear(): void
    {
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        $this->assertEquals(2, $this->collection->count());
        
        $result = $this->collection->clear();
        
        $this->assertSame($this->collection, $result);
        $this->assertEquals(0, $this->collection->count());
        $this->assertEmpty($this->collection->all());
    }

    /**
     * Test contains method
     */
    public function testContains(): void
    {
        $item1 = new TestItem(1, 'Item One');
        $item2 = new TestItem(2, 'Item Two');
        
        $this->collection->addObject($item1);
        
        $this->assertTrue($this->collection->contains($item1));
        $this->assertFalse($this->collection->contains($item2));
        
        // Test with a similar but different object
        $similarItem = new TestItem(1, 'Item One');
        $this->assertFalse($this->collection->contains($similarItem));
    }

    /**
     * Test filter method
     */
    public function testFilter(): void
    {
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two'),
            new TestItem(3, 'Item Three'),
            new TestItem(4, 'Item Four')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        // Filter for even IDs
        $filtered = $this->collection->filter(function ($item) {
            return $item->id % 2 === 0;
        });
        
        $this->assertInstanceOf(Collection::class, $filtered);
        $this->assertEquals(2, $filtered->count());
        $this->assertEquals(2, $filtered->first()->id);
        $this->assertEquals(4, $filtered->last()->id);
        
        // Original collection should remain unchanged
        $this->assertEquals(4, $this->collection->count());
    }

    /**
     * Test map method
     */
    public function testMap(): void
    {
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two'),
            new TestItem(3, 'Item Three')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        $result = $this->collection->map(function ($item) {
            return $item->id * 2;
        });
        
        $this->assertIsArray($result);
        $this->assertEquals([2, 4, 6], $result);
    }

    /**
     * Test first method
     */
    public function testFirst(): void
    {
        // Test on empty collection
        $this->assertNull($this->collection->first());
        
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        $first = $this->collection->first();
        
        $this->assertInstanceOf(TestItem::class, $first);
        $this->assertEquals(1, $first->id);
        $this->assertEquals('Item One', $first->name);
    }

    /**
     * Test last method
     */
    public function testLast(): void
    {
        // Test on empty collection
        $this->assertNull($this->collection->last());
        
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        $last = $this->collection->last();
        
        $this->assertInstanceOf(TestItem::class, $last);
        $this->assertEquals(2, $last->id);
        $this->assertEquals('Item Two', $last->name);
    }

    /**
     * Test ArrayAccess implementation - offsetExists
     */
    public function testOffsetExists(): void
    {
        $this->collection->addObject(new TestItem(1));
        
        $this->assertTrue(isset($this->collection[0]));
        $this->assertFalse(isset($this->collection[1]));
    }

    /**
     * Test ArrayAccess implementation - offsetGet
     */
    public function testOffsetGet(): void
    {
        $item = new TestItem(1, 'Test Item');
        $this->collection->addObject($item);
        
        $this->assertSame($item, $this->collection[0]);
        $this->assertNull($this->collection[1]);
    }

    /**
     * Test ArrayAccess implementation - offsetSet
     */
    public function testOffsetSet(): void
    {
        $item1 = new TestItem(1);
        $item2 = new TestItem(2);
        
        // Test with null offset
        $this->collection[] = $item1;
        $this->assertEquals(1, $this->collection->count());
        $this->assertSame($item1, $this->collection[0]);
        
        // Test with specific offset
        $this->collection[1] = $item2;
        $this->assertEquals(2, $this->collection->count());
        $this->assertSame($item2, $this->collection[1]);
    }

    /**
     * Test ArrayAccess implementation - offsetUnset
     */
    public function testOffsetUnset(): void
    {
        $this->collection->addObject(new TestItem(1));
        $this->collection->addObject(new TestItem(2));
        
        $this->assertEquals(2, $this->collection->count());
        
        unset($this->collection[0]);
        
        $this->assertEquals(1, $this->collection->count());
        $this->assertFalse(isset($this->collection[0]));
        $this->assertTrue(isset($this->collection[1]));
    }

    /**
     * Test Iterator implementation
     */
    public function testIterator(): void
    {
        $items = [
            new TestItem(1, 'Item One'),
            new TestItem(2, 'Item Two'),
            new TestItem(3, 'Item Three')
        ];
        
        foreach ($items as $item) {
            $this->collection->addObject($item);
        }
        
        // Test iteration
        $i = 0;
        foreach ($this->collection as $key => $item) {
            $this->assertEquals($i, $key);
            $this->assertSame($items[$i], $item);
            $i++;
        }
        
        $this->assertEquals(3, $i);
    }

    /**
     * Test Countable implementation
     */
    public function testCount(): void
    {
        $this->assertEquals(0, count($this->collection));
        
        $this->collection->addObject(new TestItem(1));
        $this->assertEquals(1, count($this->collection));
        
        $this->collection->addObject(new TestItem(2));
        $this->assertEquals(2, count($this->collection));
        
        $this->collection->clear();
        $this->assertEquals(0, count($this->collection));
    }
}