<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Entity\Viber;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class ViberTest extends TestCase
{
    /**
     * @var string
     */
    private $testSender;

    /**
     * @var string
     */
    private $testPhone;

    /**
     * @var string
     */
    private $testText;

    /**
     * @var string
     */
    private $testImageUrl;

    /**
     * @var array
     */
    private $testAction;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $metadataMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testSender = 'nexus';
        $this->testPhone = '358451086128';
        $this->testText = 'Test message';
        $this->testImageUrl = 'https://example.com/logo.png';
        $this->testAction = [
            'title' => 'Link',
            'url' => 'https://example.com',
        ];
        
        // Mock the MessageMetadata class
        $this->metadataMock = $this->createMock(MessageMetadata::class);
        $this->metadataMock->method('toArray')->willReturn([
            'callback_url' => 'https://example.com/callback',
            'report_url' => 'https://example.com/report',
            'ttl' => 600
        ]);
        $this->metadataMock->method('ttl')->willReturn(600);
    }

    /**
     * Test constructor sets properties correctly
     */
    public function testConstructor(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $this->assertInstanceOf(Viber::class, $viber, 'Constructor should return a Viber instance');
        $this->assertEquals($this->testSender, $viber->getSender(), 'getSender() should return the sender provided to constructor');
        $this->assertEquals($this->testPhone, $viber->getPhone(), 'getPhone() should return the phone provided to constructor');
        $this->assertEquals($this->testText, $viber->getText(), 'getText() should return the text provided to constructor');
        $this->assertEquals($this->testImageUrl, $viber->getImageUrl(), 'getImageUrl() should return the image URL provided to constructor');
        $this->assertEquals($this->testAction, $viber->getAction(), 'getAction() should return the action provided to constructor');
        $this->assertSame($this->metadataMock, $viber->getMetadata(), 'getMetadata() should return the metadata provided to constructor');
    }

    /**
     * Test constructor with default values
     */
    public function testConstructorWithDefaults(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock
        );
        
        $this->assertInstanceOf(Viber::class, $viber, 'Constructor should return a Viber instance');
        $this->assertEquals('', $viber->getImageUrl(), 'getImageUrl() should return empty string when not provided');
        $this->assertEquals([], $viber->getAction(), 'getAction() should return empty array when not provided or invalid');
    }

    /**
     * Test getSender method
     */
    public function testGetSender(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $this->assertEquals($this->testSender, $viber->getSender(), 'getSender() should return the sender provided to constructor');
    }

    /**
     * Test getImageUrl method
     */
    public function testGetImageUrl(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $this->assertEquals($this->testImageUrl, $viber->getImageUrl(), 'getImageUrl() should return the image URL provided to constructor');
    }

    /**
     * Test getAction method
     */
    public function testGetAction(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $this->assertEquals($this->testAction, $viber->getAction(), 'getAction() should return the action provided to constructor');
    }

    /**
     * Test checkAction method with invalid action
     */
    public function testCheckActionWithInvalidAction(): void
    {
        // Test with invalid URL
        $invalidAction = [
            'title' => 'Test',
            'url' => 'not-a-valid-url'
        ];
        
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $invalidAction
        );
        
        $this->assertEquals([], $viber->getAction(), 'getAction() should return empty array when URL is invalid');
        
        // Test with missing keys
        $incompleteAction = [
            'title' => 'Test'
            // missing url
        ];
        
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $incompleteAction
        );
        
        $this->assertEquals([], $viber->getAction(), 'getAction() should return empty array when required keys are missing');
    }

    /**
     * Test prepare method
     */
    public function testPrepare(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        // Use reflection to access the protected prepare method
        $reflection = new ReflectionMethod(Viber::class, 'prepare');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($viber);
        
        $this->assertIsArray($result, 'prepare() should return an array');
        $this->assertArrayHasKey('sender', $result, 'prepare() result should contain sender key');
        $this->assertEquals($this->testSender, $result['sender'], 'prepare() result should have correct sender value');
        $this->assertArrayHasKey('image', $result, 'prepare() result should contain image key');
        $this->assertEquals($this->testImageUrl, $result['image'], 'prepare() result should have correct image value');
        $this->assertArrayHasKey('action', $result, 'prepare() result should contain action key');
        $this->assertEquals($this->testAction, $result['action'], 'prepare() result should have correct action value');
    }

    /**
     * Test maxTtl method
     */
    public function testMaxTtl(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        // Use reflection to access the protected maxTtl method
        $reflection = new ReflectionMethod(Viber::class, 'maxTtl');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($viber);
        
        $this->assertEquals(Config::MEDIUM_TTL, $result, 'maxTtl() should return Config::MEDIUM_TTL');
    }

    /**
     * Test setCustomId and getCustomId methods
     */
    public function testCustomId(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $customId = 'test-custom-id-789';
        $viber->setCustomId($customId);
        
        $this->assertEquals($customId, $viber->getCustomId(), 'getCustomId() should return the custom ID set with setCustomId()');
    }

    /**
     * Test getMessage method
     */
    public function testGetMessage(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        $message = $viber->getMessage();
        
        $this->assertIsArray($message, 'getMessage() should return an array');
        $this->assertArrayHasKey('sender', $message, 'message should contain sender key');
        $this->assertEquals($this->testSender, $message['sender'], 'message should have correct sender value');
        $this->assertArrayHasKey('image', $message, 'message should contain image key');
        $this->assertEquals($this->testImageUrl, $message['image'], 'message should have correct image value');
        $this->assertArrayHasKey('action', $message, 'message should contain action key');
        $this->assertEquals($this->testAction, $message['action'], 'message should have correct action value');
        $this->assertArrayHasKey('phone', $message, 'message should contain phone key');
        $this->assertEquals($this->testPhone, $message['phone'], 'message should have correct phone value');
        $this->assertArrayHasKey('text', $message, 'message should contain text key');
        $this->assertEquals($this->testText, $message['text'], 'message should have correct text value');
        
        // Check that metadata values are included
        $this->assertArrayHasKey('callback_url', $message, 'message should contain callback_url from metadata');
        $this->assertArrayHasKey('report_url', $message, 'message should contain report_url from metadata');
        $this->assertArrayHasKey('ttl', $message, 'message should contain ttl from metadata');
    }

    /**
     * Test setStatus and getStatus methods
     */
    public function testStatus(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        // Create a mock Status object
        $statusMock = $this->createMock(\Nexus\Message\Sdk\Entity\Status::class);
        $statusMock->method('getStatus')->willReturn('delivered');
        
        // Set the status
        $viber->setStatus($statusMock);
        
        // Get the status
        $status = $viber->getStatus();
        
        $this->assertSame($statusMock, $status, 'getStatus() should return the Status object set with setStatus()');
    }

    /**
     * Test price and parts properties via magic __set method
     */
    public function testPriceAndParts(): void
    {
        $viber = new Viber(
            $this->testSender, 
            $this->testPhone, 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
        
        // Set price and parts using magic __set
        $viber->price = 4.5;
        $viber->parts = 3;
        
        $this->assertEquals(4.5, $viber->getPrice(), 'getPrice() should return the price set via magic __set');
        $this->assertEquals(3, $viber->getParts(), 'getParts() should return the parts set via magic __set');
    }

    /**
     * Test invalid phone number handling
     */
    public function testInvalidPhone(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Viber(
            $this->testSender, 
            '', 
            $this->testText, 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
    }

    /**
     * Test empty text handling
     */
    public function testEmptyText(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Viber(
            $this->testSender, 
            $this->testPhone, 
            '', 
            $this->metadataMock,
            $this->testImageUrl,
            $this->testAction
        );
    }
}