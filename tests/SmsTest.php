<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Sms;
use Imobis\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class SmsTest extends TestCase
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
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $metadataMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testSender = 'imobis.ru';
        $this->testPhone = '79939819173';
        $this->testText = 'Test message';
        
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
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        $this->assertInstanceOf(Sms::class, $sms, 'Constructor should return a Sms instance');
        $this->assertEquals($this->testSender, $sms->getSender(), 'getSender() should return the sender provided to constructor');
        $this->assertEquals($this->testPhone, $sms->getPhone(), 'getPhone() should return the phone provided to constructor');
        $this->assertEquals($this->testText, $sms->getText(), 'getText() should return the text provided to constructor');
        $this->assertSame($this->metadataMock, $sms->getMetadata(), 'getMetadata() should return the metadata provided to constructor');
    }

    /**
     * Test getSender method
     */
    public function testGetSender(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        $this->assertEquals($this->testSender, $sms->getSender(), 'getSender() should return the sender provided to constructor');
    }

    /**
     * Test prepare method
     */
    public function testPrepare(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected prepare method
        $reflection = new ReflectionMethod(Sms::class, 'prepare');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($sms);
        
        $this->assertIsArray($result, 'prepare() should return an array');
        $this->assertArrayHasKey('sender', $result, 'prepare() result should contain sender key');
        $this->assertEquals($this->testSender, $result['sender'], 'prepare() result should have correct sender value');
    }

    /**
     * Test maxTtl method
     */
    public function testMaxTtl(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected maxTtl method
        $reflection = new ReflectionMethod(Sms::class, 'maxTtl');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($sms);
        
        $this->assertEquals(Config::MAX_TTL, $result, 'maxTtl() should return Config::MAX_TTL');
    }

    /**
     * Test setCustomId and getCustomId methods
     */
    public function testCustomId(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        $customId = 'test-custom-id-123';
        $sms->setCustomId($customId);
        
        $this->assertEquals($customId, $sms->getCustomId(), 'getCustomId() should return the custom ID set with setCustomId()');
    }

    /**
     * Test getMessage method
     */
    public function testGetMessage(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        $message = $sms->getMessage();
        
        $this->assertIsArray($message, 'getMessage() should return an array');
        $this->assertArrayHasKey('sender', $message, 'message should contain sender key');
        $this->assertEquals($this->testSender, $message['sender'], 'message should have correct sender value');
        $this->assertArrayHasKey('phone', $message, 'message should contain phone key');
        $this->assertEquals($this->testPhone, $message['phone'], 'message should have correct phone value');
        $this->assertArrayHasKey('text', $message, 'message should contain text key');
        
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
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Create a mock Status object
        $statusMock = $this->createMock(\Imobis\Sdk\Entity\Status::class);
        $statusMock->method('getStatus')->willReturn('delivered');
        
        // Set the status
        $sms->setStatus($statusMock);
        
        // Get the status
        $status = $sms->getStatus();
        
        $this->assertSame($statusMock, $status, 'getStatus() should return the Status object set with setStatus()');
    }

    /**
     * Test price and parts properties via magic __set method
     */
    public function testPriceAndParts(): void
    {
        $sms = new Sms($this->testSender, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Set price and parts using magic __set
        $sms->price = 3.5;
        $sms->parts = 2;
        
        $this->assertEquals(3.5, $sms->getPrice(), 'getPrice() should return the price set via magic __set');
        $this->assertEquals(2, $sms->getParts(), 'getParts() should return the parts set via magic __set');
    }

    /**
     * Test invalid phone number handling
     */
    public function testInvalidPhone(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Sms($this->testSender, '', $this->testText, $this->metadataMock);
    }

    /**
     * Test empty text handling
     */
    public function testEmptyText(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Sms($this->testSender, $this->testPhone, '', $this->metadataMock);
    }
}