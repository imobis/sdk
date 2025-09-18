<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Entity\Reply;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

class ReplyTest extends TestCase
{
    /**
     * @var string
     */
    private $testMessageId;

    /**
     * @var string
     */
    private $testText;

    /**
     * @var string
     */
    private $testDate;

    /**
     * @var string|null
     */
    private $testCustomId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testMessageId = 'msg-123456789';
        $this->testText = 'Test reply message';
        $this->testDate = '2025-09-01 16:40:00';
        $this->testCustomId = 'custom-123';
    }

    /**
     * Test constructor with all parameters
     */
    public function testConstructorWithAllParameters(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        $this->assertInstanceOf(Reply::class, $reply, 'Constructor should return a Reply instance');
        $this->assertEquals($this->testMessageId, $reply->getMessageId(), 'getMessageId() should return the messageId provided to constructor');
        $this->assertEquals($this->testText, $reply->getText(), 'getText() should return the text provided to constructor');
        $this->assertEquals($this->testDate, $reply->getDate(), 'getDate() should return the date provided to constructor');
        $this->assertEquals($this->testCustomId, $reply->getCustomId(), 'getCustomId() should return the customId provided to constructor');
    }

    /**
     * Test constructor without optional customId parameter
     */
    public function testConstructorWithoutCustomId(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate
        );
        
        $this->assertInstanceOf(Reply::class, $reply, 'Constructor should return a Reply instance');
        $this->assertEquals($this->testMessageId, $reply->getMessageId(), 'getMessageId() should return the messageId provided to constructor');
        $this->assertEquals($this->testText, $reply->getText(), 'getText() should return the text provided to constructor');
        $this->assertEquals($this->testDate, $reply->getDate(), 'getDate() should return the date provided to constructor');
        $this->assertNull($reply->getCustomId(), 'getCustomId() should return null when customId is not provided');
    }

    /**
     * Test getMessageId method
     */
    public function testGetMessageId(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        $result = $reply->getMessageId();
        
        $this->assertEquals($this->testMessageId, $result, 'getMessageId() should return the messageId provided to constructor');
    }

    /**
     * Test getText method
     */
    public function testGetText(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        $result = $reply->getText();
        
        $this->assertEquals($this->testText, $result, 'getText() should return the text provided to constructor');
    }

    /**
     * Test getDate method
     */
    public function testGetDate(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        $result = $reply->getDate();
        
        $this->assertEquals($this->testDate, $result, 'getDate() should return the date provided to constructor');
    }

    /**
     * Test getCustomId method with customId provided
     */
    public function testGetCustomIdWithCustomIdProvided(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        $result = $reply->getCustomId();
        
        $this->assertEquals($this->testCustomId, $result, 'getCustomId() should return the customId provided to constructor');
    }

    /**
     * Test getCustomId method without customId provided
     */
    public function testGetCustomIdWithoutCustomIdProvided(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate
        );
        
        $result = $reply->getCustomId();
        
        $this->assertNull($result, 'getCustomId() should return null when customId is not provided');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        // Initial changes should be empty
        $this->assertEmpty($reply->getChanges(), 'Initial changes should be empty');
        
        // Call touch
        $reply->touch();
        
        // Changes should now contain processed timestamp
        $changes = $reply->getChanges();
        $this->assertArrayHasKey('processed', $changes, 'Changes should contain processed key after touch() call');
        $this->assertIsInt($changes['processed'], 'processed value in changes should be an integer timestamp');
        
        // Call touch again (should not update processed timestamp)
        $initialProcessed = $changes['processed'];
        $reply->touch();
        $newChanges = $reply->getChanges();
        
        $this->assertEquals($initialProcessed, $newChanges['processed'], 'processed timestamp should not change on subsequent touch() calls');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        // Initial original should be empty
        $this->assertEmpty($reply->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $reply->getProperties();
        
        // Original should now contain data
        $original = $reply->getOriginal();
        $this->assertArrayHasKey('messageId', $original, 'Original should contain messageId key');
        $this->assertArrayHasKey('customId', $original, 'Original should contain customId key');
        $this->assertArrayHasKey('text', $original, 'Original should contain text key');
        $this->assertArrayHasKey('date', $original, 'Original should contain date key');
        $this->assertEquals($this->testMessageId, $original['messageId'], 'Original messageId should match the messageId provided to constructor');
        $this->assertEquals($this->testCustomId, $original['customId'], 'Original customId should match the customId provided to constructor');
        $this->assertEquals($this->testText, $original['text'], 'Original text should match the text provided to constructor');
        $this->assertEquals($this->testDate, $original['date'], 'Original date should match the date provided to constructor');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        // Initial changes should be empty
        $this->assertEmpty($reply->getChanges(), 'Initial changes should be empty');
        
        // Call touch to add to changes
        $reply->touch();
        
        // Changes should contain processed timestamp
        $changes = $reply->getChanges();
        $this->assertArrayHasKey('processed', $changes, 'Changes should contain processed key after touch() call');
        $this->assertIsInt($changes['processed'], 'processed value in changes should be an integer timestamp');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate,
            $this->testCustomId
        );
        
        // Call touch
        $reply->touch();
        
        $properties = $reply->getProperties();
        
        $this->assertArrayHasKey('messageId', $properties, 'Properties should contain messageId key');
        $this->assertArrayHasKey('customId', $properties, 'Properties should contain customId key');
        $this->assertArrayHasKey('text', $properties, 'Properties should contain text key');
        $this->assertArrayHasKey('date', $properties, 'Properties should contain date key');
        $this->assertEquals($this->testMessageId, $properties['messageId'], 'Properties messageId should match the messageId');
        $this->assertEquals($this->testCustomId, $properties['customId'], 'Properties customId should match the customId');
        $this->assertEquals($this->testText, $properties['text'], 'Properties text should match the text');
        $this->assertEquals($this->testDate, $properties['date'], 'Properties date should match the date');
    }

    /**
     * Test getProperties method without customId
     */
    public function testGetPropertiesWithoutCustomId(): void
    {
        $reply = new Reply(
            $this->testMessageId,
            $this->testText,
            $this->testDate
        );
        
        $properties = $reply->getProperties();
        
        $this->assertArrayHasKey('messageId', $properties, 'Properties should contain messageId key');
        $this->assertArrayHasKey('customId', $properties, 'Properties should contain customId key');
        $this->assertArrayHasKey('text', $properties, 'Properties should contain text key');
        $this->assertArrayHasKey('date', $properties, 'Properties should contain date key');
        $this->assertEquals($this->testMessageId, $properties['messageId'], 'Properties messageId should match the messageId');
        $this->assertNull($properties['customId'], 'Properties customId should be null');
        $this->assertEquals($this->testText, $properties['text'], 'Properties text should match the text');
        $this->assertEquals($this->testDate, $properties['date'], 'Properties date should match the date');
    }
}