<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Core\Collections\SimpleRouteCollection;
use Nexus\Message\Sdk\Entity\Sms;
use Nexus\Message\Sdk\Entity\Status;
use Nexus\Message\Sdk\Entity\Telegram;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Test class for SimpleRouteCollection
 */
class SimpleRouteCollectionTest extends TestCase
{
    /**
     * @var SimpleRouteCollection
     */
    private $collection;

    /**
     * @var MessageMetadata
     */
    private $metadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new SimpleRouteCollection();
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
        $this->assertInstanceOf(SimpleRouteCollection::class, $this->collection);
        $this->assertEquals(0, $this->collection->count());
        $this->assertNull($this->collection->getStatus());
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
     * Test addObject method with multiple messages (should only allow 1)
     */
    public function testAddObjectWithMultipleMessages(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        
        // First message should be added successfully
        $result1 = $this->collection->addObject($sms);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->collection->count());
        
        // Second message should not be added (SimpleRouteCollection only allows 1 message)
        $result2 = $this->collection->addObject($telegram);
        $this->assertFalse($result2);
        $this->assertEquals(1, $this->collection->count());
    }

    /**
     * Test addObject method with same message type
     */
    public function testAddObjectWithSameMessageType(): void
    {
        $sms1 = new Sms('sender1', '358451086128', 'Test SMS 1', $this->metadata);
        $sms2 = new Sms('sender2', '358451086128', 'Test SMS 2', $this->metadata);
        
        // First SMS should be added successfully
        $result1 = $this->collection->addObject($sms1);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->collection->count());
        
        // Second SMS should not be added (collection is full)
        $result2 = $this->collection->addObject($sms2);
        $this->assertFalse($result2);
        $this->assertEquals(1, $this->collection->count());
    }

    /**
     * Test getQueryData method
     */
    public function testGetQueryData(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        $queryData = $this->collection->getQueryData();
        
        // Verify the structure of the query data
        $this->assertIsArray($queryData);
        $this->assertArrayHasKey('report', $queryData);
        $this->assertArrayHasKey('reply', $queryData);
        $this->assertArrayHasKey('ttl', $queryData);
        $this->assertArrayHasKey('text', $queryData);
        $this->assertArrayHasKey('phone', $queryData);
        $this->assertArrayHasKey('sender', $queryData);
    }

    /**
     * Test getStatus method
     */
    public function testGetStatus(): void
    {
        // Initially status should be null
        $this->assertNull($this->collection->getStatus());
        
        // Set a status
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        
        $this->collection->setStatus($status);
        
        // Now status should be set
        $this->assertSame($status, $this->collection->getStatus());
    }

    /**
     * Test setStatus method
     */
    public function testSetStatus(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        
        // Method should return $this for chaining
        $result = $this->collection->setStatus($status);
        $this->assertSame($this->collection, $result);
        
        // Status should be set
        $this->assertSame($status, $this->collection->getStatus());
        
        // Check if the status was injected into the message
        $message = $this->collection->first();
        $this->assertSame($status, $message->getStatus());
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
        $this->assertEquals('test-entity-id', $message->getStatus()->getEntityId());
    }

    /**
     * Test injection method (indirectly through setStatus)
     */
    public function testInjection(): void
    {
        // Add a message
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        // Create a status with entity ID
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        
        $this->collection->setStatus($status);
        
        // Check if the status was injected into the message
        $message = $this->collection->first();
        $this->assertSame($status, $message->getStatus());
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
        $this->assertEquals('test-entity-id', $message->getStatus()->getEntityId());
        
        // Create a status without entity ID method
        $mockStatus = $this->createMock(Status::class);
        $mockStatus->method('getStatus')->willReturn('delivered');
        // No getEntityId method
        
        // Reset collection
        $this->setUp();
        $this->collection->addObject($sms);
        
        // This should not throw an error even though getEntityId doesn't exist
        $this->collection->setStatus($mockStatus);
        
        // Verify the mock status was set
        $this->assertSame($mockStatus, $this->collection->getStatus());
        $message = $this->collection->first();
        $this->assertSame($mockStatus, $message->getStatus());
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
    }
}