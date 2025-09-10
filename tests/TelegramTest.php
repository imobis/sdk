<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Telegram;
use Imobis\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class TelegramTest extends TestCase
{
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
        $this->testPhone = '79939819173';
        $this->testText = '1234 test 567 message 89';
        
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
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        $this->assertInstanceOf(Telegram::class, $telegram, 'Constructor should return a Telegram instance');
        $this->assertEquals($this->testPhone, $telegram->getPhone(), 'getPhone() should return the phone provided to constructor');
        $this->assertEquals($this->testText, $telegram->getText(), 'getText() should return the text provided to constructor');
        $this->assertSame($this->metadataMock, $telegram->getMetadata(), 'getMetadata() should return the metadata provided to constructor');
    }

    /**
     * Test getVerificationCode method
     */
    public function testGetVerificationCode(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        $result = $telegram->getVerificationCode($this->testText);
        
        $this->assertEquals('123456789', $result, 'getVerificationCode() should extract only numeric characters from text');
        
        // Test with different text
        $textWithoutNumbers = 'test message';
        $result = $telegram->getVerificationCode($textWithoutNumbers);
        
        $this->assertEquals('', $result, 'getVerificationCode() should return empty string when no numbers are present');
    }

    /**
     * Test prepare method
     */
    public function testPrepare(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected prepare method
        $reflection = new ReflectionMethod(Telegram::class, 'prepare');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($telegram);
        
        $this->assertIsArray($result, 'prepare() should return an array');
        $this->assertArrayHasKey('text', $result, 'prepare() result should contain text key');
        $this->assertEquals('123456789', $result['text'], 'prepare() result should have text with only numeric characters');
    }

    /**
     * Test maxTtl method
     */
    public function testMaxTtl(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected maxTtl method
        $reflection = new ReflectionMethod(Telegram::class, 'maxTtl');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($telegram);
        
        $this->assertEquals(Config::MIN_TTL, $result, 'maxTtl() should return Config::MIN_TTL');
    }

    /**
     * Test setCustomId and getCustomId methods
     */
    public function testCustomId(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        $customId = 'test-custom-id-456';
        $telegram->setCustomId($customId);
        
        $this->assertEquals($customId, $telegram->getCustomId(), 'getCustomId() should return the custom ID set with setCustomId()');
    }

    /**
     * Test getMessage method
     */
    public function testGetMessage(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        $message = $telegram->getMessage();
        
        $this->assertIsArray($message, 'getMessage() should return an array');
        $this->assertArrayHasKey('text', $message, 'message should contain text key');
        $this->assertEquals('123456789', $message['text'], 'message should have text with only numeric characters');
        $this->assertArrayHasKey('phone', $message, 'message should contain phone key');
        $this->assertEquals($this->testPhone, $message['phone'], 'message should have correct phone value');
        
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
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        // Create a mock Status object
        $statusMock = $this->createMock(\Imobis\Sdk\Entity\Status::class);
        $statusMock->method('getStatus')->willReturn('delivered');
        
        // Set the status
        $telegram->setStatus($statusMock);
        
        // Get the status
        $status = $telegram->getStatus();
        
        $this->assertSame($statusMock, $status, 'getStatus() should return the Status object set with setStatus()');
    }

    /**
     * Test price and parts properties via magic __set method
     */
    public function testPriceAndParts(): void
    {
        $telegram = new Telegram($this->testPhone, $this->testText, $this->metadataMock);
        
        // Set price and parts using magic __set
        $telegram->price = 2.5;
        $telegram->parts = 1;
        
        $this->assertEquals(2.5, $telegram->getPrice(), 'getPrice() should return the price set via magic __set');
        $this->assertEquals(1, $telegram->getParts(), 'getParts() should return the parts set via magic __set');
    }

    /**
     * Test invalid phone number handling
     */
    public function testInvalidPhone(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Telegram('', $this->testText, $this->metadataMock);
    }

    /**
     * Test empty text handling
     */
    public function testEmptyText(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Telegram($this->testPhone, '', $this->metadataMock);
    }
}