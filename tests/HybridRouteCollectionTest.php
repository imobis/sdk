<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Core\Collections\HybridRouteCollection;
use Imobis\Sdk\Entity\Sms;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Entity\Telegram;
use Imobis\Sdk\Entity\Viber;
use Imobis\Sdk\Entity\Vk;
use Imobis\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Test class for HybridRouteCollection
 */
class HybridRouteCollectionTest extends TestCase
{
    /**
     * @var HybridRouteCollection
     */
    private $collection;

    /**
     * @var MessageMetadata
     */
    private $metadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new HybridRouteCollection();
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
        $this->assertInstanceOf(HybridRouteCollection::class, $this->collection);
        $this->assertEquals(0, $this->collection->count());
        $this->assertNull($this->collection->getStatus());
    }

    /**
     * Test addObject method with valid message
     */
    public function testAddObjectWithValidMessage(): void
    {
        $sms = new Sms('sender', '79939819173', 'Test message', $this->metadata);
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
     * Test addObject method with multiple messages (should allow up to 3)
     */
    public function testAddObjectWithMultipleMessages(): void
    {
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
        $telegram = new Telegram('79939819173', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '79939819173', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $vk = new Vk(5965316, '79939819173', 'Test VK', $this->metadata);
        
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
        
        // Fourth message should not be added (HybridRouteCollection only allows 3 messages)
        $result4 = $this->collection->addObject($vk);
        $this->assertFalse($result4);
        $this->assertEquals(3, $this->collection->count());
    }

    /**
     * Test addObject method with same message type (should not allow duplicates)
     */
    public function testAddObjectWithSameMessageType(): void
    {
        $sms1 = new Sms('sender1', '79939819173', 'Test SMS 1', $this->metadata);
        $sms2 = new Sms('sender2', '79967933144', 'Test SMS 2', $this->metadata);
        
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
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
        $telegram = new Telegram('79939819173', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '79939819173', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        
        $this->collection->addObject($sms);
        $this->collection->addObject($telegram);
        $this->collection->addObject($viber);
        
        $queryData = $this->collection->getQueryData();
        
        // Verify the structure of the query data
        $this->assertIsArray($queryData);
        $this->assertArrayHasKey('report', $queryData);
        $this->assertArrayHasKey('ttl', $queryData);
        $this->assertArrayHasKey('route', $queryData);
        
        // Verify the route array
        $this->assertIsArray($queryData['route']);
        $this->assertCount(3, $queryData['route']);
        
        // Verify each route has the correct channel
        $channels = array_column($queryData['route'], 'channel');
        $this->assertContains('sms', $channels);
        $this->assertContains('telegram', $channels);
        $this->assertContains('viber', $channels);
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
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
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
        // Add multiple messages
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
        $telegram = new Telegram('79939819173', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '79939819173', 'Test Viber', $this->metadata, 'https://example.com/image.png');

        $this->collection->addObject($telegram);
        $this->collection->addObject($viber);
        $this->collection->addObject($sms);
        
        // Create a status with entity ID
        $entityId = '252eee3b-d35f-4433-8634-4a40448b2a1e';
        $status = new Status('delivered');
        $status->setEntityId($entityId);
        
        $this->collection->setStatus($status);
        
        // Check if the status was injected into all messages
        foreach ($this->collection as $message) {
            $this->assertSame($status, $message->getStatus());
            $this->assertEquals('delivered', $message->getStatus()->getStatus());
            $this->assertEquals($entityId, $message->getStatus()->getEntityId());
            $this->assertEquals($entityId, $message->getId());
        }

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