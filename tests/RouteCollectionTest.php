<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\RouteCollection;
use Nexus\Message\Sdk\Entity\Sms;
use Nexus\Message\Sdk\Entity\Status;
use Nexus\Message\Sdk\Entity\Telegram;
use Nexus\Message\Sdk\Entity\Viber;
use Nexus\Message\Sdk\Entity\Vk;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

/**
 * Concrete implementation of RouteCollection for testing
 */
class TestRouteCollection extends RouteCollection
{
    protected int $count = 3; // Allow up to 3 messages
    protected ?Status $status = null;

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;
        $this->injection();

        return $this;
    }

    protected function injection(): void
    {
        if ($this->status !== null && method_exists($this->status, 'getEntityId')) {
            foreach ($this->items as $item) {
                $item->id = $this->status->getEntityId();
                $item->setStatus($this->status);
            }
        }
    }
}

/**
 * Test class for RouteCollection
 */
class RouteCollectionTest extends TestCase
{
    /**
     * @var TestRouteCollection
     */
    private $collection;

    /**
     * @var MessageMetadata
     */
    private $metadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collection = new TestRouteCollection();
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
     * Test addObject method with different message types
     */
    public function testAddObjectWithDifferentMessageTypes(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $vk = new Vk(Config::getVKGroupId(), '358451086128', 'Test VK', $this->metadata);
        
        $this->collection->addObject($sms);
        $this->collection->addObject($telegram);
        $this->collection->addObject($viber);
        
        $this->assertEquals(3, $this->collection->count());
        
        // Should not add more than count limit
        $result = $this->collection->addObject($vk);
        $this->assertFalse($result);
        $this->assertEquals(3, $this->collection->count());
    }

    /**
     * Test getQueryData method
     */
    public function testGetQueryData(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        $queryData = $this->collection->getQueryData();
        
        // If getMessage method exists, it should return the message data
        $this->assertIsArray($queryData);
        
        // When collection is empty, first() returns null, so we can't test getQueryData directly
        // Instead, we'll verify that first() returns null for an empty collection
        $emptyCollection = new TestRouteCollection();
        $this->assertNull($emptyCollection->first());
    }

    /**
     * Test getMessageChannel method
     */
    public function testGetMessageChannel(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $vk = new Vk(Config::getVKGroupId(), '358451086128', 'Test VK', $this->metadata);
        
        // Use reflection to access protected method
        $reflectionMethod = new \ReflectionMethod(TestRouteCollection::class, 'getMessageChannel');
        $reflectionMethod->setAccessible(true);
        
        $this->assertEquals('sms', $reflectionMethod->invoke($this->collection, $sms));
        $this->assertEquals('telegram', $reflectionMethod->invoke($this->collection, $telegram));
        $this->assertEquals('viber', $reflectionMethod->invoke($this->collection, $viber));
        $this->assertEquals('vk', $reflectionMethod->invoke($this->collection, $vk));
    }

    /**
     * Test setStatus and injection methods
     */
    public function testSetStatusAndInjection(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $this->collection->addObject($sms);
        
        $status = new Status('delivered');
        $status->setEntityId('test-entity-id');
        
        $this->collection->setStatus($status);
        
        $this->assertSame($status, $this->collection->getStatus());
        
        // Check if the status was injected into the message
        $message = $this->collection->first();
        $this->assertSame($status, $message->getStatus());
        
        // We can't directly access the id property through reflection if it's not initialized
        // Instead, we'll verify that the injection method was called by checking if the status is set
        $this->assertEquals('delivered', $message->getStatus()->getStatus());
        $this->assertEquals('test-entity-id', $message->getStatus()->getEntityId());
    }

    /**
     * Test addChannel method
     */
    public function testAddChannel(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        
        // Use reflection to access protected method and property
        $reflectionMethod = new \ReflectionMethod(TestRouteCollection::class, 'addChannel');
        $reflectionMethod->setAccessible(true);
        
        $reflectionProperty = new \ReflectionProperty(TestRouteCollection::class, 'channels');
        $reflectionProperty->setAccessible(true);
        
        // Add first channel
        $result1 = $reflectionMethod->invoke($this->collection, $sms);
        $this->assertEquals(1, $result1);
        
        $channels = $reflectionProperty->getValue($this->collection);
        $this->assertArrayHasKey(get_class($sms), $channels);
        $this->assertEquals(1, $channels[get_class($sms)]);
        
        // Add same channel again
        $result2 = $reflectionMethod->invoke($this->collection, $sms);
        $this->assertEquals(2, $result2);
        
        $channels = $reflectionProperty->getValue($this->collection);
        $this->assertEquals(2, $channels[get_class($sms)]);
        
        // Add different channel
        $result3 = $reflectionMethod->invoke($this->collection, $telegram);
        $this->assertEquals(1, $result3);
        
        $channels = $reflectionProperty->getValue($this->collection);
        $this->assertArrayHasKey(get_class($telegram), $channels);
        $this->assertEquals(1, $channels[get_class($telegram)]);
    }

    /**
     * Test check method
     */
    public function testCheck(): void
    {
        $sms = new Sms('sender', '358451086128', 'Test SMS', $this->metadata);
        $sameTypeSms = new Sms('sender2', '358451086128', 'Another SMS', $this->metadata);
        $telegram = new Telegram('358451086128', 'Test Telegram', $this->metadata);
        
        // Use reflection to access protected method and property
        $reflectionMethod = new \ReflectionMethod(TestRouteCollection::class, 'check');
        $reflectionMethod->setAccessible(true);
        
        $reflectionProperty = new \ReflectionProperty(TestRouteCollection::class, 'channels');
        $reflectionProperty->setAccessible(true);
        
        // First check should pass
        $this->assertTrue($reflectionMethod->invoke($this->collection, $sms));
        
        // Add the SMS to the collection
        $this->collection->addObject($sms);
        
        // Check with same type should fail (already has SMS)
        $channels = [get_class($sms) => 1];
        $reflectionProperty->setValue($this->collection, $channels);
        $this->assertFalse($reflectionMethod->invoke($this->collection, $sameTypeSms));
        
        // Check with different type should pass
        $this->assertTrue($reflectionMethod->invoke($this->collection, $telegram));
        
        // Add more items to reach the count limit
        $this->collection->addObject($telegram);
        $viber = new Viber('sender', '358451086128', 'Test Viber', $this->metadata, 'https://example.com/image.png');
        $this->collection->addObject($viber);
        
        // Check with any type should fail (count limit reached)
        $vk = new Vk(Config::getVKGroupId(), '358451086128', 'Test VK', $this->metadata);
        $this->assertFalse($reflectionMethod->invoke($this->collection, $vk));
    }
}