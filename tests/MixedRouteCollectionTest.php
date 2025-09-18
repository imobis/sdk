<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Collections\MixedRouteCollection;
use Nexus\Message\Sdk\Entity\Sms;
use Nexus\Message\Sdk\Entity\Status;
use Nexus\Message\Sdk\Entity\Telegram;
use Nexus\Message\Sdk\Entity\Viber;
use Nexus\Message\Sdk\Entity\Vk;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Test class for MixedRouteCollection
 */
class MixedRouteCollectionTest extends TestCase
{
    /**
     * @var MixedRouteCollection
     */
    private $collection;

    /**
     * @var MessageMetadata
     */
    private $metadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new MixedRouteCollection();
        $this->metadata = MessageMetadata::create(
            'https://example.com/callback',
            'https://example.com/report',
            600
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->collection = null;
        $this->metadata = null;
    }

    /**
     * Test constructor creates a valid collection
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(MixedRouteCollection::class, $this->collection);
        $this->assertEquals(0, $this->collection->count());
        $this->assertNull($this->collection->getStatuses());
    }

    /**
     * Test addObject method with valid message
     */
    public function testAddObjectWithValidMessage(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test message', $this->metadata);
        $result = $this->collection->addObject($sms);
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->collection->count());
        $this->assertSame($sms, $this->collection->first());
    }

    /**
     * Test addObject method with invalid object type
     */
    public function testAddObjectWithInvalidType(): void
    {
        $wrongItem = new \stdClass();
        $result = $this->collection->addObject($wrongItem);
        
        $this->assertFalse($result);
        $this->assertEquals(0, $this->collection->count());
    }

    /**
     * Test addObject method with multiple messages (should allow up to 1000)
     */
    public function testAddObjectWithMultipleMessages(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $vk = new Vk(Config::getVKGroupId(), '358451086128', 'Test VK', $this->metadata);
        
        // First message should be added successfully
        $result1 = $this->collection->addObject($sms);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->collection->count());
        
        // Second message should be added successfully
        $result2 = $this->collection->addObject($telegram);
        $this->assertTrue($result2);
        $this->assertEquals(2, $this->collection->count());
        
        // Third message should be added successfully
        $result3 = $this->collection->addObject($viber);
        $this->assertTrue($result3);
        $this->assertEquals(3, $this->collection->count());
        
        // Fourth message should also be added successfully (MixedRouteCollection allows up to 1000 messages)
        $result4 = $this->collection->addObject($vk);
        $this->assertTrue($result4);
        $this->assertEquals(4, $this->collection->count());
    }

    /**
     * Test addObject method with same message type (should not allow duplicates)
     */
    public function testAddObjectWithSameMessageType(): void
    {
        $sms1 = new Sms('sender1', '358451086128', 'Test SMS 1', $this->metadata);
        $sms2 = new Sms('sender2', '358451086128', 'Test SMS 2', $this->metadata);
        
        // First SMS should be added successfully
        $result1 = $this->collection->addObject($sms1);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->collection->count());
        
        // Second SMS should not be added (same type)
        $result2 = $this->collection->addObject($sms2);
        $this->assertFalse($result2);
        $this->assertEquals(1, $this->collection->count());
    }

    /**
     * Test getQueryData method
     */
    public function testGetQueryData(): void
    {
        // Add multiple messages of different types
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        
        $this->collection->addObject($sms);
        $this->collection->addObject($telegram);
        $this->collection->addObject($viber);
        
        $queryData = $this->collection->getQueryData();
        
        // Verify the structure of the query data
        $this->assertIsArray($queryData);
        $this->assertNotEmpty($queryData);
        
        // Each item in the array should have metadata and route
        foreach ($queryData as $item) {
            $this->assertArrayHasKey('report', $item);
            $this->assertArrayHasKey('reply', $item);
            $this->assertArrayHasKey('ttl', $item);
            $this->assertArrayHasKey('route', $item);
            
            // Verify the route array
            $this->assertIsArray($item['route']);
            $this->assertNotEmpty($item['route']);
            
            // Each route should have a channel
            foreach ($item['route'] as $route) {
                $this->assertArrayHasKey('channel', $route);
            }
        }
    }

    /**
     * Test getStatuses method
     */
    public function testGetStatuses(): void
    {
        // Initially statuses should be null
        $this->assertNull($this->collection->getStatuses());
        
        // Create a collection of statuses
        $statusCollection = new Collection(Status::class);
        $status1 = new Status('delivered');
        $status1->setEntityId('test-entity-id-1');
        $status2 = new Status('read');
        $status2->setEntityId('test-entity-id-2');
        
        $statusCollection->addObject($status1);
        $statusCollection->addObject($status2);
        
        $this->collection->setStatuses($statusCollection);
        
        // Now statuses should be set
        $this->assertSame($statusCollection, $this->collection->getStatuses());
    }

    /**
     * Test setStatuses method
     */
    public function testSetStatuses(): void
    {
        // Create a collection of statuses
        $statusCollection = new Collection(Status::class);
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        $statusCollection->addObject($status);
        
        // Method should return $this for chaining
        $result = $this->collection->setStatuses($statusCollection);
        $this->assertSame($this->collection, $result);
        
        // Statuses should be set
        $this->assertSame($statusCollection, $this->collection->getStatuses());
    }

    /**
     * Test postPrepare method
     */
    public function testPostPrepare(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        // Create a collection of statuses
        $statusCollection = new Collection(Status::class);
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        $statusCollection->addObject($status);
        
        $this->collection->setStatuses($statusCollection);
        
        // Call postPrepare which should trigger injection
        $this->collection->postPrepare();
        
        // Check if the status was injected into the message
        $message = $this->collection->first();
        $this->assertSame($status, $message->getStatus());
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
        $this->assertEquals('test-entity-id', $message->getStatus()->getEntityId());
    }

    /**
     * Test injection method (indirectly through postPrepare)
     */
    public function testInjection(): void
    {
        // Add multiple messages
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        
        $this->collection->addObject($sms);
        $this->collection->addObject($telegram);
        $this->collection->addObject($viber);
        
        // Create a collection of statuses with matching keys
        $statusCollection = new Collection(Status::class);
        $status1 = new Status('delivered');
        $status1->setEntityId('test-entity-id-1');
        $status2 = new Status('read');
        $status2->setEntityId('test-entity-id-2');
        $status3 = new Status('sent');
        $status3->setEntityId('test-entity-id-3');
        
        $statusCollection->addObject($status1);
        $statusCollection->addObject($status2);
        $statusCollection->addObject($status3);
        
        $this->collection->setStatuses($statusCollection);
        $this->collection->postPrepare();
        
        // Check if the statuses were injected into the messages
        $messages = $this->collection->all();
        $statuses = $statusCollection->all();
        
        foreach ($messages as $key => $message) {
            $this->assertSame($statuses[$key], $message->getStatus());
            $this->assertEquals($statuses[$key]->getStatus(), $message->getStatus()->getStatus());
            $this->assertEquals($statuses[$key]->getEntityId(), $message->getStatus()->getEntityId());
        }
        
        // Test with null statuses
        $this->setUp(); // Reset collection
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        // This should not throw an error
        $this->collection->setStatuses(null);
        $this->collection->postPrepare();
        
        // We can't test the status directly because it's not initialized
        // Just verify that postPrepare doesn't throw an error
        $this->assertTrue(true);
        
        // Test with a status that doesn't have getEntityId method
        $this->setUp(); // Reset collection
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        // Initialize the status first to avoid uninitialized property error
        $status = new Status('delivered');
        $sms->setStatus($status);
        
        $mockStatusCollection = new Collection(Status::class);
        $mockStatus = $this->createMock(Status::class);
        $mockStatus->method('getStatus')->willReturn('delivered');
        // No getEntityId method
        $mockStatusCollection->addObject($mockStatus);
        
        // This should not throw an error
        $this->collection->setStatuses($mockStatusCollection);
        $this->collection->postPrepare();
        
        // Message should have the mock status
        $message = $this->collection->first();
        $this->assertSame($mockStatus, $message->getStatus());
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
    }
}