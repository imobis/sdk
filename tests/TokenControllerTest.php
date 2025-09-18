<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Controllers\TokenController;
use Nexus\Message\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class TokenControllerTest extends TestCase
{
    /**
     * @var string
     */
    private $testApiKey;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenMock;

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
        $controller = new TokenController($this->tokenMock);
        
        $this->assertInstanceOf(TokenController::class, $controller, 'Constructor should return a TokenController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(TokenController::class);
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
        $controller = new TokenController($this->tokenMock);
        
        // Mock the getData method to return test data
        $testData = [
            'login' => 'new_test_login',
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TokenController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Set up the Token mock to expect login update and touch call
        $this->tokenMock->expects($this->once())
            ->method('__set')
            ->with('login', 'new_test_login');
        
        $this->tokenMock->expects($this->once())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read();
        
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
        $controller = new TokenController($inactiveTokenMock);
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TokenController::class)
            ->setConstructorArgs([$inactiveTokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // getData should still be called even with inactive token
        $controllerMock->expects($this->once())
            ->method('getData')
            ->willReturn([]);
        
        // Call the read method
        $result = $controllerMock->read();
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when token is inactive');
    }

    /**
     * Test read method with same login data
     */
    public function testReadWithSameLoginData(): void
    {
        // Create a controller with the mock token
        $controller = new TokenController($this->tokenMock);
        
        // Mock the getData method to return test data with the same login
        $testData = [
            'login' => 'test_login', // Same as in tokenMock->getProperties()
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TokenController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Set up the Token mock to expect no login update or touch call
        $this->tokenMock->expects($this->never())
            ->method('__set');
        
        $this->tokenMock->expects($this->never())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read();
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with collection and filters parameters
     */
    public function testReadWithCollectionAndFilters(): void
    {
        // Create a controller with the mock token
        $controller = new TokenController($this->tokenMock);
        
        // Create a mock collection
        $collectionMock = $this->createMock(\Nexus\Message\Sdk\Core\Collections\Collection::class);
        
        // Create test filters
        $filters = ['test_filter' => 'test_value'];
        
        // Mock the getData method to return test data
        $testData = [
            'login' => 'test_login',
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TokenController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data and expect the filters parameter
        $controllerMock->expects($this->once())
            ->method('getData')
            ->with('read', $filters)
            ->willReturn($testData);
        
        // Call the read method with collection and filters
        $result = $controllerMock->read($collectionMock, $filters);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with missing login in response
     */
    public function testReadWithMissingLoginInResponse(): void
    {
        // Create a controller with the mock token
        $controller = new TokenController($this->tokenMock);
        
        // Mock the getData method to return test data without login
        $testData = [
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TokenController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Set up the Token mock to expect no login update or touch call
        $this->tokenMock->expects($this->never())
            ->method('__set');
        
        $this->tokenMock->expects($this->never())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read();
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }
}