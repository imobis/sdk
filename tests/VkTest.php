<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Entity\Vk;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class VkTest extends TestCase
{
    /**
     * @var int
     */
    private $testGroupId;

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
        $this->testGroupId = Config::getVKGroupId();
        $this->testPhone = '358451086128';
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
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        $this->assertInstanceOf(Vk::class, $vk, 'Constructor should return a Vk instance');
        $this->assertEquals($this->testGroupId, $vk->getGroup(), 'getGroup() should return the group ID provided to constructor');
        $this->assertEquals($this->testPhone, $vk->getPhone(), 'getPhone() should return the phone provided to constructor');
        $this->assertEquals($this->testText, $vk->getText(), 'getText() should return the text provided to constructor');
        $this->assertSame($this->metadataMock, $vk->getMetadata(), 'getMetadata() should return the metadata provided to constructor');
    }

    /**
     * Test getGroup method
     */
    public function testGetGroup(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        $result = $vk->getGroup();
        
        $this->assertEquals($this->testGroupId, $result, 'getGroup() should return the group ID provided to constructor');
    }

    /**
     * Test prepare method
     */
    public function testPrepare(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected prepare method
        $reflection = new ReflectionMethod(Vk::class, 'prepare');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($vk);
        
        $this->assertIsArray($result, 'prepare() should return an array');
        $this->assertArrayHasKey('group', $result, 'prepare() result should contain group key');
        $this->assertEquals($this->testGroupId, $result['group'], 'prepare() result should have correct group value');
    }

    /**
     * Test maxTtl method
     */
    public function testMaxTtl(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Use reflection to access the protected maxTtl method
        $reflection = new ReflectionMethod(Vk::class, 'maxTtl');
        $reflection->setAccessible(true);
        
        $result = $reflection->invoke($vk);
        
        $this->assertEquals(Config::MEDIUM_TTL, $result, 'maxTtl() should return Config::MEDIUM_TTL');
    }

    /**
     * Test setCustomId and getCustomId methods
     */
    public function testCustomId(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        $customId = 'test-custom-id-vk';
        $vk->setCustomId($customId);
        
        $this->assertEquals($customId, $vk->getCustomId(), 'getCustomId() should return the custom ID set with setCustomId()');
    }

    /**
     * Test getMessage method
     */
    public function testGetMessage(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        $message = $vk->getMessage();
        
        $this->assertIsArray($message, 'getMessage() should return an array');
        $this->assertArrayHasKey('group', $message, 'message should contain group key');
        $this->assertEquals($this->testGroupId, $message['group'], 'message should have correct group value');
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
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Create a mock Status object
        $statusMock = $this->createMock(\Nexus\Message\Sdk\Entity\Status::class);
        $statusMock->method('getStatus')->willReturn('delivered');
        
        // Set the status
        $vk->setStatus($statusMock);
        
        // Get the status
        $status = $vk->getStatus();
        
        $this->assertSame($statusMock, $status, 'getStatus() should return the Status object set with setStatus()');
    }

    /**
     * Test price and parts properties via magic __set method
     */
    public function testPriceAndParts(): void
    {
        $vk = new Vk($this->testGroupId, $this->testPhone, $this->testText, $this->metadataMock);
        
        // Set price and parts using magic __set
        $vk->price = 1.5;
        $vk->parts = 1;
        
        $this->assertEquals(1.5, $vk->getPrice(), 'getPrice() should return the price set via magic __set');
        $this->assertEquals(1, $vk->getParts(), 'getParts() should return the parts set via magic __set');
    }

    /**
     * Test invalid phone number handling
     */
    public function testInvalidPhone(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Vk($this->testGroupId, '', $this->testText, $this->metadataMock);
    }

    /**
     * Test empty text handling
     */
    public function testEmptyText(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Check failed');
        
        new Vk($this->testGroupId, $this->testPhone, '', $this->metadataMock);
    }
}