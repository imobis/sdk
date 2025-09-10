<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Controllers\MessageController;
use Imobis\Sdk\Core\Collections\ChannelRouteCollection;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Collections\HybridRouteCollection;
use Imobis\Sdk\Core\Collections\MixedRouteCollection;
use Imobis\Sdk\Core\Collections\RouteCollection;
use Imobis\Sdk\Core\Collections\SimpleRouteCollection;
use Imobis\Sdk\Entity\Error;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Entity\Token;
use Imobis\Sdk\Exceptions\SenderException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class MessageControllerTest extends TestCase
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
    private $channelRouteCollectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $simpleRouteCollectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $hybridRouteCollectionMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $mixedRouteCollectionMock;

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
        
        // Mock the RouteCollection classes
        $this->channelRouteCollectionMock = $this->createMock(ChannelRouteCollection::class);
        $this->simpleRouteCollectionMock = $this->createMock(SimpleRouteCollection::class);
        $this->hybridRouteCollectionMock = $this->createMock(HybridRouteCollection::class);
        $this->mixedRouteCollectionMock = $this->createMock(MixedRouteCollection::class);
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
        $controller = new MessageController($this->tokenMock);
        
        $this->assertInstanceOf(MessageController::class, $controller, 'Constructor should return a MessageController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(MessageController::class);
        $tokenProperty = $reflection->getParentClass()->getProperty('token');
        $tokenProperty->setAccessible(true);
        $token = $tokenProperty->getValue($controller);
        
        $this->assertSame($this->tokenMock, $token, 'token property should be set to the token provided to constructor');
    }

    /**
     * Test send method with ChannelRouteCollection
     */
    public function testSendWithChannelRouteCollection(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the ChannelRouteCollection mock
        $this->channelRouteCollectionMock->method('getChannel')
            ->willReturn('sms');
        
        // Create a partial mock of the controller to mock the channel method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['channel'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('channel')
            ->with($this->channelRouteCollectionMock)
            ->willReturn($testData);
        
        // Call the send method
        $result = $controllerMock->send($this->channelRouteCollectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'send() should return the data from channel()');
    }

    /**
     * Test send method with SimpleRouteCollection
     */
    public function testSendWithSimpleRouteCollection(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the simple method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['simple'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('simple')
            ->with($this->simpleRouteCollectionMock)
            ->willReturn($testData);
        
        // Call the send method
        $result = $controllerMock->send($this->simpleRouteCollectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'send() should return the data from simple()');
    }

    /**
     * Test send method with HybridRouteCollection
     */
    public function testSendWithHybridRouteCollection(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the hydrid method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['hydrid'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('hydrid')
            ->with($this->hybridRouteCollectionMock)
            ->willReturn($testData);
        
        // Call the send method
        $result = $controllerMock->send($this->hybridRouteCollectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'send() should return the data from hydrid()');
    }

    /**
     * Test send method with MixedRouteCollection
     */
    public function testSendWithMixedRouteCollection(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the mixed method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['mixed'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('mixed')
            ->with($this->mixedRouteCollectionMock)
            ->willReturn($testData);
        
        // Call the send method
        $result = $controllerMock->send($this->mixedRouteCollectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'send() should return the data from mixed()');
    }

    /**
     * Test send method with unknown RouteCollection type
     */
    public function testSendWithUnknownRouteCollectionType(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a mock of a generic RouteCollection
        $routeCollectionMock = $this->createMock(RouteCollection::class);
        
        // Call the send method
        $result = $controller->send($routeCollectionMock);
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'send() should return an empty array for unknown RouteCollection types');
    }

    /**
     * Test channel method
     */
    public function testChannel(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the ChannelRouteCollection mock
        $this->channelRouteCollectionMock->method('getChannel')
            ->willReturn('sms');
        
        // Create a partial mock of the controller to mock the request method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['request'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('request')
            ->with($this->channelRouteCollectionMock, 'channel', ['route' => 'sms'])
            ->willReturn($testData);
        
        // Call the channel method
        $result = $this->callProtectedMethod($controllerMock, 'channel', [$this->channelRouteCollectionMock]);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'channel() should return the data from request()');
    }

    /**
     * Test simple method
     */
    public function testSimple(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the request method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['request'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('request')
            ->with($this->simpleRouteCollectionMock, 'simple')
            ->willReturn($testData);
        
        // Call the simple method
        $result = $this->callProtectedMethod($controllerMock, 'simple', [$this->simpleRouteCollectionMock]);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'simple() should return the data from request()');
    }

    /**
     * Test hydrid method
     */
    public function testHydrid(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the request method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['request'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('request')
            ->with($this->hybridRouteCollectionMock, 'hydrid')
            ->willReturn($testData);
        
        // Call the hydrid method
        $result = $this->callProtectedMethod($controllerMock, 'hydrid', [$this->hybridRouteCollectionMock]);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'hydrid() should return the data from request()');
    }

    /**
     * Test mixed method
     */
    public function testMixed(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the request method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['request'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('request')
            ->with($this->mixedRouteCollectionMock, 'mixed')
            ->willReturn($testData);
        
        // Call the mixed method
        $result = $this->callProtectedMethod($controllerMock, 'mixed', [$this->mixedRouteCollectionMock]);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'mixed() should return the data from request()');
    }

    /**
     * Test request method with successful response
     */
    public function testRequestWithSuccessfulResponse(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the RouteCollection mock
        $routeCollectionMock = $this->createMock(RouteCollection::class);
        $routeCollectionMock->method('getQueryData')
            ->willReturn(['text' => 'Test message']);
        
        // Create a partial mock of the controller to mock the getData and injectionStatuses methods
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData', 'injectionStatuses'])
            ->getMock();
        
        // Set up the mock to return test data
        $testData = ['result' => 'success', 'id' => '123', 'status' => 'sent'];
        $controllerMock->method('getData')
            ->with('test_action', ['text' => 'Test message'], ['test_option' => 'test_value'])
            ->willReturn($testData);
        
        // Set up the mock to return a collection
        $statusCollectionMock = $this->createMock(Collection::class);
        $controllerMock->method('injectionStatuses')
            ->with($testData, $routeCollectionMock)
            ->willReturn($statusCollectionMock);
        
        // Call the request method
        $result = $this->callProtectedMethod($controllerMock, 'request', [$routeCollectionMock, 'test_action', ['test_option' => 'test_value']]);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'request() should return the data from getData()');
    }

    /**
     * Test request method with invalid sender error
     */
    public function testRequestWithInvalidSenderError(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the RouteCollection mock
        $routeCollectionMock = $this->createMock(RouteCollection::class);
        $routeCollectionMock->method('getQueryData')
            ->willReturn(['sender' => 'invalid_sender', 'text' => 'Test message']);
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(MessageController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return an error response
        $errorResponse = ['result' => 'error', 'desc' => 'Invalid sender'];
        $controllerMock->method('getData')
            ->willReturn($errorResponse);
        
        // Expect a SenderException
        $this->expectException(SenderException::class);
        
        // Call the request method
        $this->callProtectedMethod($controllerMock, 'request', [$routeCollectionMock, 'test_action']);
    }

    /**
     * Test injectionStatuses method with successful response for MixedRouteCollection
     */
    public function testInjectionStatusesWithSuccessfulResponseForMixedRouteCollection(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the MixedRouteCollection mock
        $this->mixedRouteCollectionMock->expects($this->once())
            ->method('setStatuses')
            ->with($this->isInstanceOf(Collection::class));
        
        // Set up the response
        $response = [
            'result' => 'success',
            'id' => ['123', '456'],
            'status' => 'sent'
        ];
        
        // Call the injectionStatuses method
        $result = $this->callProtectedMethod($controller, 'injectionStatuses', [$response, $this->mixedRouteCollectionMock]);
        
        // Verify the result is a Collection
        $this->assertInstanceOf(Collection::class, $result, 'injectionStatuses() should return a Collection');
        
        // Verify the collection contains Status objects
        $this->assertEquals(2, $result->count(), 'Collection should contain 2 Status objects');
    }

    /**
     * Test injectionStatuses method with successful response for other RouteCollection types
     */
    public function testInjectionStatusesWithSuccessfulResponseForOtherRouteCollectionTypes(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the ChannelRouteCollection mock
        $this->channelRouteCollectionMock->expects($this->once())
            ->method('setStatus')
            ->with($this->isInstanceOf(Status::class));
        
        // Set up the response
        $response = [
            'result' => 'success',
            'id' => '123',
            'status' => 'sent'
        ];
        
        // Call the injectionStatuses method
        $result = $this->callProtectedMethod($controller, 'injectionStatuses', [$response, $this->channelRouteCollectionMock]);
        
        // Verify the result is a Collection
        $this->assertInstanceOf(Collection::class, $result, 'injectionStatuses() should return a Collection');
        
        // Verify the collection contains a Status object
        $this->assertEquals(1, $result->count(), 'Collection should contain 1 Status object');
    }

    /**
     * Test injectionStatuses method with error response
     */
    public function testInjectionStatusesWithErrorResponse(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Set up the RouteCollection mock
        $routeCollectionMock = $this->createMock(RouteCollection::class);
        
        // Set up the response
        $response = [
            'result' => 'error',
            'desc' => 'Test error'
        ];
        
        // Call the injectionStatuses method
        $result = $this->callProtectedMethod($controller, 'injectionStatuses', [$response, $routeCollectionMock]);
        
        // Verify the result is a Collection
        $this->assertInstanceOf(Collection::class, $result, 'injectionStatuses() should return a Collection');
        
        // Verify the collection contains a Status object with error
        $this->assertEquals(1, $result->count(), 'Collection should contain 1 Status object');
        $status = $result->first();
        $this->assertEquals('error', $status->getStatus(), 'Status should be "error"');
        $this->assertInstanceOf(Error::class, $status->getError(), 'Status should have an Error object');
        $this->assertEquals('Test error', $status->getError()->message, 'Error message should be "Test error"');
    }

    /**
     * Test injectionStatuses method with postPrepare method
     */
    public function testInjectionStatusesWithPostPrepareMethod(): void
    {
        // Create a controller with the mock token
        $controller = new MessageController($this->tokenMock);
        
        // Create a mock RouteCollection with postPrepare method
        $routeCollectionMock = $this->getMockBuilder(RouteCollection::class)
            ->disableOriginalConstructor()
            ->addMethods(['postPrepare'])
            ->getMockForAbstractClass();
        
        // Expect postPrepare to be called
        $routeCollectionMock->expects($this->once())
            ->method('postPrepare');
        
        // Set up the response
        $response = [
            'result' => 'success',
            'id' => '123',
            'status' => 'sent'
        ];
        
        // Call the injectionStatuses method
        $result = $this->callProtectedMethod($controller, 'injectionStatuses', [$response, $routeCollectionMock]);
        
        // Verify the result is a Collection
        $this->assertInstanceOf(Collection::class, $result, 'injectionStatuses() should return a Collection');
    }
}