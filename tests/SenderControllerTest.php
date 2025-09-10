<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Controllers\SenderController;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Entity\Sender;
use Imobis\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class SenderControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $testApiKey;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $senderMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $collectionMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test API key
        $this->testApiKey = Config::TEST_API_KEY;
        
        // Mock the Token class
        $this->tokenMock = $this->createMock(Token::class);
        $this->tokenMock->method('getToken')->willReturn($this->testApiKey);
        $this->tokenMock->method('validate')->willReturn(true);
        $this->tokenMock->method('getActive')->willReturn(true);
        $this->tokenMock->method('getProperties')->willReturn([
            'login' => 'test_login',
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ]);
        
        // Mock the Sender class
        $this->senderMock = $this->getMockBuilder(Sender::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->senderMock->method('getSender')->willReturn('test_sender');
        $this->senderMock->method('getChannel')->willReturn('sms');
        $this->senderMock->method('checked')->willReturn(true);
        $this->senderMock->method('getProperties')->willReturn([
            'sender' => 'test_sender',
            'channel' => 'sms',
            'checked' => true
        ]);
        
        // Mock the Collection class
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the Collection mock to return true for addObject and the sender mock for last
        $this->collectionMock->method('addObject')->willReturn(true);
        $this->collectionMock->method('last')->willReturn($this->senderMock);
    }
    
    /**
     * Call a protected method using reflection
     */
    private function callProtectedMethod(object $object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionMethod(get_class($object), $methodName);
        $reflection->setAccessible(true);
        
        return $reflection->invokeArgs($object, $parameters);
    }

    /**
     * Test constructor initializes properties correctly
     */
    public function testConstructor(): void
    {
        $controller = new SenderController($this->tokenMock);
        
        $this->assertInstanceOf(SenderController::class, $controller, 'Constructor should return a SenderController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(SenderController::class);
        $tokenProperty = $reflection->getParentClass()->getProperty('token');
        $tokenProperty->setAccessible(true);
        $token = $tokenProperty->getValue($controller);
        
        $this->assertSame($this->tokenMock, $token, 'token property should be set to the token provided to constructor');
    }

    /**
     * Test read method with active token
     */
    public function testReadWithActiveToken(): void
    {
        // Create a controller with the mock token
        $controller = new SenderController($this->tokenMock);
        
        // Mock the getData method to return test data
        $testData = [
            'sms' => ['test_sender_1', 'test_sender_2'],
            'viber' => ['test_sender_3']
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(SenderController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['channel' => 'all'])
            ->willReturn($testData);
        
        // Set up the Collection mock to expect addObject and last calls
        $this->collectionMock->expects($this->exactly(3))
            ->method('addObject')
            ->withConsecutive(
                [$this->isInstanceOf(Sender::class)],
                [$this->isInstanceOf(Sender::class)],
                [$this->isInstanceOf(Sender::class)]
            );
        
        $this->collectionMock->expects($this->exactly(3))
            ->method('last');
        
        // Call the read method
        $result = $controllerMock->read($this->collectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with inactive token
     */
    public function testReadWithInactiveToken(): void
    {
        // Create a token mock that returns inactive
        $inactiveTokenMock = $this->createMock(Token::class);
        $inactiveTokenMock->method('getActive')->willReturn(false);
        
        // Create a controller with the inactive token mock
        $controller = new SenderController($inactiveTokenMock);
        
        // Set up the Collection mock to expect no calls
        $this->collectionMock->expects($this->never())
            ->method('addObject');
        
        $this->collectionMock->expects($this->never())
            ->method('last');
        
        // Call the read method
        $result = $controller->read($this->collectionMock);
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when token is inactive');
    }

    /**
     * Test read method with empty data
     */
    public function testReadWithEmptyData(): void
    {
        // Create a controller with the mock token
        $controller = new SenderController($this->tokenMock);
        
        // Mock the getData method to return empty data
        $testData = [];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(SenderController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['channel' => 'all'])
            ->willReturn($testData);
        
        // Set up the Collection mock to expect no calls
        $this->collectionMock->expects($this->never())
            ->method('addObject');
        
        $this->collectionMock->expects($this->never())
            ->method('last');
        
        // Call the read method
        $result = $controllerMock->read($this->collectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with non-array senders
     */
    public function testReadWithNonArraySenders(): void
    {
        // Create a controller with the mock token
        $controller = new SenderController($this->tokenMock);
        
        // Mock the getData method to return data with non-array senders
        $testData = [
            'sms' => 'test_sender_1'
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(SenderController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['channel' => 'all'])
            ->willReturn($testData);
        
        // Set up the Collection mock to expect addObject and last calls
        $this->collectionMock->expects($this->once())
            ->method('addObject')
            ->with($this->isInstanceOf(Sender::class));
        
        $this->collectionMock->expects($this->once())
            ->method('last');
        
        // Call the read method
        $result = $controllerMock->read($this->collectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with specific channel filter
     */
    public function testReadWithSpecificChannelFilter(): void
    {
        // Create a controller with the mock token
        $controller = new SenderController($this->tokenMock);
        
        // Create test filters
        $filters = ['channel' => 'sms'];
        
        // Mock the getData method to return test data
        $testData = [
            'sms' => ['test_sender_1', 'test_sender_2']
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(SenderController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data and expect the filters parameter
        $controllerMock->expects($this->once())
            ->method('getData')
            ->with('read', ['channel' => 'sms'])
            ->willReturn($testData);
        
        // Set up the Collection mock to expect addObject and last calls
        $this->collectionMock->expects($this->exactly(2))
            ->method('addObject')
            ->withConsecutive(
                [$this->isInstanceOf(Sender::class)],
                [$this->isInstanceOf(Sender::class)]
            );
        
        $this->collectionMock->expects($this->exactly(2))
            ->method('last');
        
        // Call the read method with collection and filters
        $result = $controllerMock->read($this->collectionMock, $filters);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }
}