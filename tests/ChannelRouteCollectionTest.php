<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Core\Collections\ChannelRouteCollection;
use Imobis\Sdk\Entity\Sms;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Entity\Telegram;
use Imobis\Sdk\Entity\Viber;
use Imobis\Sdk\Entity\Vk;
use Imobis\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Test class for ChannelRouteCollection
 */
class ChannelRouteCollectionTest extends TestCase
{
    /**
     * @var ChannelRouteCollection
     */
    private $collection;

    /**
     * @var MessageMetadata
     */
    private $metadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new ChannelRouteCollection();
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
        $this->assertInstanceOf(ChannelRouteCollection::class, $this->collection);
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
     * Test addObject method with multiple messages (should only allow one)
     */
    public function testAddObjectWithMultipleMessages(): void
    {
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
        $telegram = new Telegram('79939819173', 'Test Telegram', $this->metadata);
        
        // First message should be added successfully
        $result1 = $this->collection->addObject($sms);
        $this->assertTrue($result1);
        $this->assertEquals(1, $this->collection->count());
        
        // Second message should not be added (ChannelRouteCollection only allows 1 message)
        $result2 = $this->collection->addObject($telegram);
        $this->assertFalse($result2);
        $this->assertEquals(1, $this->collection->count());
    }

    /**
     * Test getChannel method
     */
    public function testGetChannel(): void
    {
        // Test with SMS
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        $this->assertEquals('sms', $this->collection->getChannel());
        
        // Reset collection
        $this->setUp();
        
        // Test with Telegram
        $telegram = new Telegram('79939819173', 'Test Telegram', $this->metadata);
        $this->collection->addObject($telegram);
        $this->assertEquals('telegram', $this->collection->getChannel());
        
        // Reset collection
        $this->setUp();
        
        // Test with Viber
        $viber = new Viber('sender', '79939819173', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $this->collection->addObject($viber);
        $this->assertEquals('viber', $this->collection->getChannel());
        
        // Reset collection
        $this->setUp();
        
        // Test with VK
        $vk = new Vk(5965316, '79939819173', 'Test VK', $this->metadata);
        $this->collection->addObject($vk);
        $this->assertEquals('vk', $this->collection->getChannel());
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
        $sms = new Sms('sender', '79939819173', 'Test SMS', $this->metadata);
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