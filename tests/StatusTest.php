<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Core\Message;
use Imobis\Sdk\Entity\Channel;
use Imobis\Sdk\Entity\Error;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Entity\Template;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

class StatusTest extends TestCase
{
    /**
     * @var string
     */
    private $testStatus;

    /**
     * @var string
     */
    private $testEntityId;

    /**
     * @var Error
     */
    private $testError;

    /**
     * @var Channel
     */
    private $testChannel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testStatus = 'delivered';
        $this->testEntityId = 'entity-123456789';
        
        // Create a mock Error object
        $this->testError = $this->createMock(Error::class);
        $this->testError->method('toArray')->willReturn(['code' => 123, 'message' => 'Test error message']);
        
        // Create a mock Channel object
        $this->testChannel = $this->createMock(Channel::class);
        $this->testChannel->method('toArray')->willReturn(['channel' => 'sms']);
        $this->testChannel->method('getChannel')->willReturn('sms');
    }
    
    /**
     * Helper method to initialize nullable typed properties in Status class
     * This is needed because PHP 7.4+ requires typed properties to be initialized
     * before they can be accessed, even if they're nullable
     */
    private function initializeNullableProperties(Status $status): void
    {
        $reflectionClass = new \ReflectionClass(Status::class);
        
        // Initialize $error property
        $errorProperty = $reflectionClass->getProperty('error');
        $errorProperty->setAccessible(true);
        $errorProperty->setValue($status, null);
        
        // Initialize $channel property
        $channelProperty = $reflectionClass->getProperty('channel');
        $channelProperty->setAccessible(true);
        $channelProperty->setValue($status, null);
    }

    /**
     * Test constructor with valid status and entityId
     */
    public function testConstructorWithValidStatusAndEntityId(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        $this->assertInstanceOf(Status::class, $status, 'Constructor should return a Status instance');
        $this->assertEquals($this->testStatus, $status->getStatus(), 'getStatus() should return the status provided to constructor');
        $this->assertEquals($this->testEntityId, $status->getEntityId(), 'getEntityId() should return the entityId provided to constructor');
        $this->assertEquals(Message::class, $status->getEntityClass(), 'getEntityClass() should return Message::class by default');
    }

    /**
     * Test constructor with invalid status
     */
    public function testConstructorWithInvalidStatus(): void
    {
        $invalidStatus = 'invalid_status';
        $status = new Status($invalidStatus, $this->testEntityId);
        
        $this->assertInstanceOf(Status::class, $status, 'Constructor should return a Status instance');
        $this->assertEquals('unknown', $status->getStatus(), 'getStatus() should return "unknown" when an invalid status is provided');
        $this->assertEquals($this->testEntityId, $status->getEntityId(), 'getEntityId() should return the entityId provided to constructor');
    }

    /**
     * Test constructor with null parameters
     */
    public function testConstructorWithNullParameters(): void
    {
        $status = new Status();
        
        $this->assertInstanceOf(Status::class, $status, 'Constructor should return a Status instance');
        $this->assertEquals('unknown', $status->getStatus(), 'getStatus() should return "unknown" when null status is provided');
        $this->assertEquals('', $status->getEntityId(), 'getEntityId() should return empty string when null entityId is provided');
    }

    /**
     * Test getError and setError methods
     */
    public function testGetErrorAndSetError(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initialize nullable properties
        $this->initializeNullableProperties($status);
        
        // Initial error should be null
        $this->assertNull($status->getError(), 'Initial error should be null');
        
        // Set error
        $result = $status->setError($this->testError);
        
        // Verify error was set
        $this->assertSame($this->testError, $status->getError(), 'getError() should return the error set with setError()');
        
        // Method should return $this for chaining
        $this->assertSame($status, $result, 'setError() should return $this for method chaining');
    }

    /**
     * Test getEntityId and setEntityId methods
     */
    public function testGetEntityIdAndSetEntityId(): void
    {
        $status = new Status($this->testStatus);
        
        // Initial entityId should be empty
        $this->assertEquals('', $status->getEntityId(), 'Initial entityId should be empty');
        
        // Set entityId
        $newEntityId = 'new-entity-id';
        $result = $status->setEntityId($newEntityId);
        
        // Verify entityId was set
        $this->assertEquals($newEntityId, $status->getEntityId(), 'getEntityId() should return the entityId set with setEntityId()');
        
        // Method should return $this for chaining
        $this->assertSame($status, $result, 'setEntityId() should return $this for method chaining');
    }

    /**
     * Test getEntityClass and setEntityClass methods
     */
    public function testGetEntityClassAndSetEntityClass(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initial entityClass should be Message::class
        $this->assertEquals(Message::class, $status->getEntityClass(), 'Initial entityClass should be Message::class');
        
        // Set entityClass to Template::class (should succeed)
        $result = $status->setEntityClass(Template::class);
        
        // Verify entityClass was set
        $this->assertTrue($result, 'setEntityClass() should return true when setting to Template::class');
        $this->assertEquals(Template::class, $status->getEntityClass(), 'getEntityClass() should return Template::class after successful setEntityClass()');
        
        // Set entityClass to an invalid class (should fail)
        $invalidClass = 'InvalidClass';
        $result = $status->setEntityClass($invalidClass);
        
        // Verify entityClass was not changed
        $this->assertFalse($result, 'setEntityClass() should return false when setting to an invalid class');
        $this->assertEquals(Template::class, $status->getEntityClass(), 'getEntityClass() should not change after failed setEntityClass()');
    }

    /**
     * Test getStatus and setStatus methods
     */
    public function testGetStatusAndSetStatus(): void
    {
        $status = new Status('sent', $this->testEntityId);
        
        // Initial status should be 'sent'
        $this->assertEquals('sent', $status->getStatus(), 'Initial status should be "sent"');
        
        // Set status to a valid value
        $newStatus = 'delivered';
        $result = $status->setStatus($newStatus);
        
        // Verify status was set
        $this->assertEquals($newStatus, $status->getStatus(), 'getStatus() should return the status set with setStatus()');
        
        // Method should return $this for chaining
        $this->assertSame($status, $result, 'setStatus() should return $this for method chaining');
        
        // Set status to an invalid value
        $invalidStatus = 'invalid_status';
        $result = $status->setStatus($invalidStatus);
        
        // Verify status was not changed
        $this->assertEquals($newStatus, $status->getStatus(), 'getStatus() should not change after setting an invalid status');
        
        // Method should still return $this for chaining
        $this->assertSame($status, $result, 'setStatus() should return $this for method chaining even when status is invalid');
    }

    /**
     * Test getChannel and setChannel methods
     */
    public function testGetChannelAndSetChannel(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initialize nullable properties
        $this->initializeNullableProperties($status);
        
        // Initial channel should be null
        $this->assertNull($status->getChannel(), 'Initial channel should be null');
        
        // Set channel
        $result = $status->setChannel($this->testChannel);
        
        // Verify channel was set
        $this->assertSame($this->testChannel, $status->getChannel(), 'getChannel() should return the channel set with setChannel()');
        
        // Method should return $this for chaining
        $this->assertSame($status, $result, 'setChannel() should return $this for method chaining');
        
        // Set channel to null
        $result = $status->setChannel(null);
        
        // Verify channel was set to null
        $this->assertNull($status->getChannel(), 'getChannel() should return null after setting channel to null');
        
        // Method should return $this for chaining
        $this->assertSame($status, $result, 'setChannel() should return $this for method chaining when setting channel to null');
    }

    /**
     * Test getInfo method
     */
    public function testGetInfo(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initial info should be empty
        $this->assertEmpty($status->getInfo(), 'Initial info should be empty');
        
        // Set info directly (it's a public property)
        $testInfo = ['key1' => 'value1', 'key2' => 'value2'];
        $status->info = $testInfo;
        
        // Verify info was set
        $this->assertEquals($testInfo, $status->getInfo(), 'getInfo() should return the info set directly');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        // For this test, we need to use reflection to test the behavior of touch()
        // since we can't directly access the private changes property
        
        // Create a reflection class for Status
        $reflectionClass = new \ReflectionClass(Status::class);
        
        // Get the changes property
        $changesProperty = $reflectionClass->getProperty('changes');
        $changesProperty->setAccessible(true);
        
        // Create a Status instance
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initial changes should be empty
        $this->assertEmpty($status->getChanges(), 'Initial changes should be empty');
        
        // Call touch without setting info (should not change anything)
        $status->touch();
        
        // Changes should still be empty
        $this->assertEmpty($status->getChanges(), 'Changes should be empty when touch is called without setting info');
        
        // Set info directly
        $status->info = ['key' => 'value'];
        
        // Set the 'info' key in changes using reflection
        $changes = $changesProperty->getValue($status);
        $changes['info'] = $status->info;
        $changesProperty->setValue($status, $changes);
        
        // Call touch
        $status->touch();
        
        // Get changes after touch
        $changesAfterTouch = $status->getChanges();
        
        // Changes should now contain initialize
        $this->assertArrayHasKey('initialize', $changesAfterTouch, 'Changes should contain initialize key after setting info and calling touch');
        $this->assertTrue($changesAfterTouch['initialize'], 'initialize value in changes should be true');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initialize nullable properties
        $this->initializeNullableProperties($status);
        
        // Initial original should be empty
        $this->assertEmpty($status->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $status->getProperties();
        
        // Original should now contain data
        $original = $status->getOriginal();
        $this->assertArrayHasKey('entityId', $original, 'Original should contain entityId key');
        $this->assertArrayHasKey('entityClass', $original, 'Original should contain entityClass key');
        $this->assertArrayHasKey('status', $original, 'Original should contain status key');
        $this->assertArrayHasKey('info', $original, 'Original should contain info key');
        $this->assertArrayHasKey('channel', $original, 'Original should contain channel key');
        $this->assertArrayHasKey('error', $original, 'Original should contain error key');
        $this->assertEquals($this->testEntityId, $original['entityId'], 'Original entityId should match the entityId provided to constructor');
        $this->assertEquals(Message::class, $original['entityClass'], 'Original entityClass should match the default entityClass');
        $this->assertEquals($this->testStatus, $original['status'], 'Original status should match the status provided to constructor');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        // Create a reflection class for Status
        $reflectionClass = new \ReflectionClass(Status::class);
        
        // Get the changes property
        $changesProperty = $reflectionClass->getProperty('changes');
        $changesProperty->setAccessible(true);
        
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initial changes should be empty
        $this->assertEmpty($status->getChanges(), 'Initial changes should be empty');
        
        // Set info directly
        $status->info = ['key' => 'value'];
        
        // Set the 'info' key in changes using reflection
        $changes = $changesProperty->getValue($status);
        $changes['info'] = $status->info;
        $changesProperty->setValue($status, $changes);
        
        // Call touch
        $status->touch();
        
        // Get changes after touch
        $changesAfterTouch = $status->getChanges();
        
        // Changes should contain info and initialize
        $this->assertArrayHasKey('info', $changesAfterTouch, 'Changes should contain info key after setting info');
        $this->assertArrayHasKey('initialize', $changesAfterTouch, 'Changes should contain initialize key after calling touch');
        $this->assertEquals(['key' => 'value'], $changesAfterTouch['info'], 'info value in changes should match the provided info');
        $this->assertTrue($changesAfterTouch['initialize'], 'initialize value in changes should be true');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Set channel and error
        $status->setChannel($this->testChannel);
        $status->setError($this->testError);
        
        // Set info
        $status->info = ['key' => 'value'];
        
        $properties = $status->getProperties();
        
        $this->assertArrayHasKey('entityId', $properties, 'Properties should contain entityId key');
        $this->assertArrayHasKey('entityClass', $properties, 'Properties should contain entityClass key');
        $this->assertArrayHasKey('status', $properties, 'Properties should contain status key');
        $this->assertArrayHasKey('info', $properties, 'Properties should contain info key');
        $this->assertArrayHasKey('channel', $properties, 'Properties should contain channel key');
        $this->assertArrayHasKey('error', $properties, 'Properties should contain error key');
        $this->assertEquals($this->testEntityId, $properties['entityId'], 'Properties entityId should match the entityId');
        $this->assertEquals(Message::class, $properties['entityClass'], 'Properties entityClass should match the entityClass');
        $this->assertEquals($this->testStatus, $properties['status'], 'Properties status should match the status');
        $this->assertEquals(['key' => 'value'], $properties['info'], 'Properties info should match the info');
        $this->assertEquals(['channel' => 'sms'], $properties['channel'], 'Properties channel should be the result of channel->toArray()');
        $this->assertEquals(['code' => 123, 'message' => 'Test error message'], $properties['error'], 'Properties error should be the result of error->toArray()');
    }

    /**
     * Test getProperties method with null channel and error
     */
    public function testGetPropertiesWithNullChannelAndError(): void
    {
        $status = new Status($this->testStatus, $this->testEntityId);
        
        // Initialize nullable properties
        $this->initializeNullableProperties($status);
        
        $properties = $status->getProperties();
        
        $this->assertArrayHasKey('channel', $properties, 'Properties should contain channel key');
        $this->assertArrayHasKey('error', $properties, 'Properties should contain error key');
        $this->assertNull($properties['channel'], 'Properties channel should be null');
        $this->assertNull($properties['error'], 'Properties error should be null');
    }
}