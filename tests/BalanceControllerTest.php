<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Controllers\BalanceController;
use Nexus\Message\Sdk\Entity\Balance;
use Nexus\Message\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class BalanceControllerTest extends TestCase
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
    private $balanceMock;

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
        
        // Mock the Balance class
        $this->balanceMock = $this->getMockBuilder(Balance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->balanceMock->method('getBalance')->willReturn(100.0);
        $this->balanceMock->method('getCurrency')->willReturn(Config::CURRENCY);
        $this->balanceMock->method('isFresh')->willReturn(true);
        $this->balanceMock->method('getProperties')->willReturn([
            'balance' => 100.0,
            'fresh' => true,
            'currency' => Config::CURRENCY
        ]);
        
        // Reset the Balance singleton instance before each test
        $this->resetBalanceInstance();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset the Balance singleton instance after each test
        $this->resetBalanceInstance();
    }

    /**
     * Reset the Balance singleton instance using reflection
     */
    private function resetBalanceInstance(): void
    {
        $reflection = new ReflectionClass(Balance::class);
        $parentClass = $reflection->getParentClass();
        
        // Reset the instances property (from Singleton parent class)
        $instancesProperty = $parentClass->getProperty('instances');
        $instancesProperty->setAccessible(true);
        $instances = $instancesProperty->getValue();
        
        if (isset($instances[Balance::class])) {
            unset($instances[Balance::class]);
            $instancesProperty->setValue(null, $instances);
        }
    }

    /**
     * Set a static property value using reflection
     */
    private function setStaticProperty(string $class, string $property, $value): void
    {
        $reflection = new ReflectionClass($class);
        
        // Try to get the property from the class
        try {
            $propertyReflection = $reflection->getProperty($property);
        } catch (\ReflectionException $e) {
            // If the property is not found in the class, try to get it from the parent class
            $parentClass = $reflection->getParentClass();
            if ($parentClass) {
                $propertyReflection = $parentClass->getProperty($property);
            } else {
                throw $e;
            }
        }
        
        $propertyReflection->setAccessible(true);
        $propertyReflection->setValue(null, $value);
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
        $controller = new BalanceController($this->tokenMock);
        
        $this->assertInstanceOf(BalanceController::class, $controller, 'Constructor should return a BalanceController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(BalanceController::class);
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
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $this->balanceMock]);
        
        // Create a controller with the mock token
        $controller = new BalanceController($this->tokenMock);
        
        // Mock the getData method to return test data
        $testData = [
            'balance' => 150.0,
            'currency' => 'USD'
        ];
        
        // Use reflection to replace the getData method
        $reflection = new ReflectionClass(BalanceController::class);
        $getDataMethod = $reflection->getMethod('getData');
        $getDataMethod->setAccessible(true);
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(BalanceController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the read method
        $result = $controllerMock->read();
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
        
        // Verify that the Balance properties would be updated
        // Note: In a real test, we would need to verify that the Balance singleton's properties are updated,
        // but since we're mocking the Balance singleton, we can't directly test this.
        // Instead, we're testing that the read method returns the expected data.
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
        $controller = new BalanceController($inactiveTokenMock);
        
        // Call the read method
        $result = $controller->read();
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when token is inactive');
    }

    /**
     * Test read method with different balance data
     */
    public function testReadWithDifferentBalanceData(): void
    {
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $this->balanceMock]);
        
        // Create a controller with the mock token
        $controller = new BalanceController($this->tokenMock);
        
        // Mock the getData method to return test data with different balance and currency
        $testData = [
            'balance' => 200.0,
            'currency' => 'EUR'
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(BalanceController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Set up the Balance mock to expect property updates
        $this->balanceMock->expects($this->once())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read();
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with same balance data
     */
    public function testReadWithSameBalanceData(): void
    {
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $this->balanceMock]);
        
        // Create a controller with the mock token
        $controller = new BalanceController($this->tokenMock);
        
        // Mock the getData method to return test data with the same balance and currency
        $testData = [
            'balance' => 100.0,
            'currency' => Config::CURRENCY
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(BalanceController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Set up the Balance mock to expect no property updates
        $this->balanceMock->expects($this->never())
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
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $this->balanceMock]);
        
        // Create a controller with the mock token
        $controller = new BalanceController($this->tokenMock);
        
        // Create a mock collection
        $collectionMock = $this->createMock(\Nexus\Message\Sdk\Core\Collections\Collection::class);
        
        // Create test filters
        $filters = ['test_filter' => 'test_value'];
        
        // Mock the getData method to return test data
        $testData = [
            'balance' => 150.0,
            'currency' => Config::CURRENCY
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(BalanceController::class)
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
}