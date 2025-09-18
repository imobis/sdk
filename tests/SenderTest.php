<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Entity\Sender;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

class SenderTest extends TestCase
{
    /**
     * @var string
     */
    private $testSender;

    /**
     * @var string
     */
    private $testChannel;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testSender = 'test_sender';
        $this->testChannel = 'sms';
    }

    /**
     * Test constructor with both parameters
     */
    public function testConstructorWithBothParameters(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        $this->assertInstanceOf(Sender::class, $sender, 'Constructor should return a Sender instance');
        $this->assertEquals($this->testSender, $sender->getSender(), 'getSender() should return the sender provided to constructor');
        $this->assertEquals($this->testChannel, $sender->getChannel(), 'getChannel() should return the channel provided to constructor');
        $this->assertFalse($sender->checked(), 'checked() should return false by default');
    }

    /**
     * Test constructor with only sender parameter
     */
    public function testConstructorWithOnlySenderParameter(): void
    {
        $sender = new Sender($this->testSender);
        
        $this->assertInstanceOf(Sender::class, $sender, 'Constructor should return a Sender instance');
        $this->assertEquals($this->testSender, $sender->getSender(), 'getSender() should return the sender provided to constructor');
        $this->assertEquals('', $sender->getChannel(), 'getChannel() should return empty string when channel is not provided');
        $this->assertFalse($sender->checked(), 'checked() should return false by default');
    }

    /**
     * Test constructor with no parameters
     */
    public function testConstructorWithNoParameters(): void
    {
        $sender = new Sender();
        
        $this->assertInstanceOf(Sender::class, $sender, 'Constructor should return a Sender instance');
        $this->assertEquals('', $sender->getSender(), 'getSender() should return empty string when sender is not provided');
        $this->assertEquals('', $sender->getChannel(), 'getChannel() should return empty string when channel is not provided');
        $this->assertFalse($sender->checked(), 'checked() should return false by default');
    }

    /**
     * Test checked method
     */
    public function testChecked(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        // Initial checked should be false
        $this->assertFalse($sender->checked(), 'Initial checked should be false');
        
        // Call touch to set checked to true
        $sender->touch();
        
        // Checked should now be true
        $this->assertTrue($sender->checked(), 'checked() should return true after touch() call');
    }

    /**
     * Test getSender method
     */
    public function testGetSender(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        $result = $sender->getSender();
        
        $this->assertEquals($this->testSender, $result, 'getSender() should return the sender provided to constructor');
    }

    /**
     * Test getChannel method
     */
    public function testGetChannel(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        $result = $sender->getChannel();
        
        $this->assertEquals($this->testChannel, $result, 'getChannel() should return the channel provided to constructor');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        // Initial changes should be empty
        $this->assertEmpty($sender->getChanges(), 'Initial changes should be empty');
        
        // Call touch
        $sender->touch();
        
        // Changes should now contain checked
        $changes = $sender->getChanges();
        $this->assertArrayHasKey('checked', $changes, 'Changes should contain checked key after touch() call');
        $this->assertTrue($changes['checked'], 'checked value in changes should be true');
        
        // checked() should now return true
        $this->assertTrue($sender->checked(), 'checked() should return true after touch() call');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        // Initial original should be empty
        $this->assertEmpty($sender->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $sender->getProperties();
        
        // Original should now contain data
        $original = $sender->getOriginal();
        $this->assertArrayHasKey('sender', $original, 'Original should contain sender key');
        $this->assertArrayHasKey('channel', $original, 'Original should contain channel key');
        $this->assertArrayHasKey('checked', $original, 'Original should contain checked key');
        $this->assertEquals($this->testSender, $original['sender'], 'Original sender should match the sender provided to constructor');
        $this->assertEquals($this->testChannel, $original['channel'], 'Original channel should match the channel provided to constructor');
        $this->assertFalse($original['checked'], 'Original checked should be false');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        // Initial changes should be empty
        $this->assertEmpty($sender->getChanges(), 'Initial changes should be empty');
        
        // Call touch to add to changes
        $sender->touch();
        
        // Changes should contain checked
        $changes = $sender->getChanges();
        $this->assertArrayHasKey('checked', $changes, 'Changes should contain checked key after touch() call');
        $this->assertTrue($changes['checked'], 'checked value in changes should be true');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $sender = new Sender($this->testSender, $this->testChannel);
        
        // Call touch
        $sender->touch();
        
        $properties = $sender->getProperties();
        
        $this->assertArrayHasKey('sender', $properties, 'Properties should contain sender key');
        $this->assertArrayHasKey('channel', $properties, 'Properties should contain channel key');
        $this->assertArrayHasKey('checked', $properties, 'Properties should contain checked key');
        $this->assertEquals($this->testSender, $properties['sender'], 'Properties sender should match the sender');
        $this->assertEquals($this->testChannel, $properties['channel'], 'Properties channel should match the channel');
        $this->assertTrue($properties['checked'], 'Properties checked should be true after touch() call');
    }
}