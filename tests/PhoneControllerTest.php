<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Controllers\PhoneController;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Entity\Phone;
use Nexus\Message\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class PhoneControllerTest extends TestCase
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
    private $phoneMock;

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
        
        // Mock the Phone class
        $this->phoneMock = $this->getMockBuilder(Phone::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up default behavior for the Phone mock
        $this->phoneMock->method('needCheck')->willReturn(true);
        $this->phoneMock->method('getNumber')->willReturn('358451086128');
        $this->phoneMock->method('getProperties')->willReturn([
            'check' => true,
            'number' => '358451086128',
            'info' => [
                'phone_number' => '',
                'status' => '',
                'country' => '',
                'operator' => '',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ]
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
        $controller = new PhoneController($this->tokenMock);
        
        $this->assertInstanceOf(PhoneController::class, $controller, 'Constructor should return a PhoneController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(PhoneController::class);
        $tokenProperty = $reflection->getParentClass()->getProperty('token');
        $tokenProperty->setAccessible(true);
        $token = $tokenProperty->getValue($controller);
        
        $this->assertSame($this->tokenMock, $token, 'token property should be set to the token provided to constructor');
    }

    /**
     * Test read method with active token and phones that need checking
     */
    public function testReadWithActiveTokenAndPhonesToCheck(): void
    {
        // Create a controller with the mock token
        $controller = new PhoneController($this->tokenMock);
        
        // Create a collection with the phone mock
        $collection = new Collection(Phone::class);
        $collection->addObject($this->phoneMock);
        
        // Mock the getData method to return test data
        $rawData = [
            [
                'phone_source' => '358451086128',
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ]
        ];
        
        // The PhoneController transforms the data using array_column
        $testData = array_column($rawData, null, 'phone_source');
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(PhoneController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['phones' => ['358451086128']])
            ->willReturn($testData);
        
        // Set up the Phone mock to expect setInfo and touch calls
        $this->phoneMock->expects($this->once())
            ->method('setInfo')
            ->with([
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ])
            ->willReturnSelf();
        
        $this->phoneMock->expects($this->once())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read($collection);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with active token but no phones that need checking
     */
    public function testReadWithActiveTokenButNoPhonesNeedChecking(): void
    {
        // Create a controller with the mock token
        $controller = new PhoneController($this->tokenMock);
        
        // Create a phone mock that doesn't need checking
        $phoneNoCheckMock = $this->getMockBuilder(Phone::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $phoneNoCheckMock->method('needCheck')->willReturn(false);
        
        // Create a collection with the phone mock
        $collection = new Collection(Phone::class);
        $collection->addObject($phoneNoCheckMock);
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(PhoneController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // getData should not be called
        $controllerMock->expects($this->never())
            ->method('getData');
        
        // Call the read method
        $result = $controllerMock->read($collection);
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when no phones need checking');
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
        $controller = new PhoneController($inactiveTokenMock);
        
        // Create a collection with the phone mock
        $collection = new Collection(Phone::class);
        $collection->addObject($this->phoneMock);
        
        // Call the read method
        $result = $controller->read($collection);
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when token is inactive');
    }

    /**
     * Test read method with empty collection
     */
    public function testReadWithEmptyCollection(): void
    {
        // Create a controller with the mock token
        $controller = new PhoneController($this->tokenMock);
        
        // Create an empty collection
        $collection = new Collection(Phone::class);
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(PhoneController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // getData should not be called
        $controllerMock->expects($this->never())
            ->method('getData');
        
        // Call the read method
        $result = $controllerMock->read($collection);
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'read() should return an empty array when collection is empty');
    }

    /**
     * Test read method with multiple phones
     */
    public function testReadWithMultiplePhones(): void
    {
        // Create a controller with the mock token
        $controller = new PhoneController($this->tokenMock);
        
        // Create a second phone mock
        $phoneMock2 = $this->getMockBuilder(Phone::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $phoneMock2->method('needCheck')->willReturn(true);
        $phoneMock2->method('getNumber')->willReturn('358451086128');
        $phoneMock2->method('getProperties')->willReturn([
            'check' => true,
            'number' => '358451086128',
            'info' => [
                'phone_number' => '',
                'status' => '',
                'country' => '',
                'operator' => '',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ]
        ]);
        
        // Create a collection with both phone mocks
        $collection = new Collection(Phone::class);
        $collection->addObject($this->phoneMock);
        $collection->addObject($phoneMock2);
        
        // Mock the getData method to return test data
        $rawData = [
            [
                'phone_source' => '358451086128',
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ],
            [
                'phone_source' => '358451086128',
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ]
        ];
        
        // The PhoneController transforms the data using array_column
        $testData = array_column($rawData, null, 'phone_source');
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(PhoneController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['phones' => ['358451086128', '358451086128']])
            ->willReturn($testData);
        
        // Set up the Phone mocks to expect setInfo and touch calls
        $this->phoneMock->expects($this->once())
            ->method('setInfo')
            ->with([
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ])
            ->willReturnSelf();
        
        $this->phoneMock->expects($this->once())
            ->method('touch');
        
        $phoneMock2->expects($this->once())
            ->method('setInfo')
            ->with([
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ])
            ->willReturnSelf();
        
        $phoneMock2->expects($this->once())
            ->method('touch');
        
        // Call the read method
        $result = $controllerMock->read($collection);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with filters parameter
     */
    public function testReadWithFilters(): void
    {
        // Create a controller with the mock token
        $controller = new PhoneController($this->tokenMock);
        
        // Create a collection with the phone mock
        $collection = new Collection(Phone::class);
        $collection->addObject($this->phoneMock);
        
        // Create test filters
        $filters = ['test_filter' => 'test_value'];
        
        // Mock the getData method to return test data
        $rawData = [
            [
                'phone_source' => '358451086128',
                'phone_number' => '+358 451086128',
                'status' => 'ok',
                'country' => 'FI',
                'operator' => 'Saunalahti Group Oyj',
                'region_id' => '',
                'region_name' => '',
                'region_timezone' => ''
            ]
        ];
        
        // The PhoneController transforms the data using array_column
        $testData = array_column($rawData, null, 'phone_source');
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(PhoneController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->with('read', ['phones' => ['358451086128']])
            ->willReturn($testData);
        
        // Call the read method with filters
        $result = $controllerMock->read($collection, $filters);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }
}