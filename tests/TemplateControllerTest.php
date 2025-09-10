<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Controllers\TemplateController;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Entity\Template;
use Imobis\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class TemplateControllerTest extends TestCase
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
    private $templateMock;

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
        
        // Mock the Template class
        $this->templateMock = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up default behavior for the Template mock
        $this->templateMock->method('getId')->willReturn('template-123');
        $this->templateMock->method('getName')->willReturn('test_template');
        $this->templateMock->method('getText')->willReturn('Test template text');
        $this->templateMock->method('getChannels')->willReturn(['sms']);
        $this->templateMock->method('getProperties')->willReturn([
            'id' => 'template-123',
            'name' => 'test_template',
            'text' => 'Test template text',
            'channels' => ['sms'],
            'group_url' => '',
            'status' => 'new',
            'comment' => 'Test comment',
            'notify_service' => '',
            'active' => false,
            'report_url' => '',
            'created' => 0,
            'updated' => 0,
            'fields' => [],
            'options' => []
        ]);
        
        // Mock the Collection class
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the Collection mock to return the template mock for all() method
        $this->collectionMock->method('all')->willReturn([$this->templateMock]);
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
        $controller = new TemplateController($this->tokenMock);
        
        $this->assertInstanceOf(TemplateController::class, $controller, 'Constructor should return a TemplateController instance');
        
        // Use reflection to check if token is set correctly
        $reflection = new ReflectionClass(TemplateController::class);
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
        $controller = new TemplateController($this->tokenMock);
        
        // Mock the getData method to return test data
        $testData = [
            [
                'id' => 'template-123',
                'name' => 'test_template',
                'text' => 'Test template text',
                'channels' => ['sms'],
                'status' => 'new'
            ]
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the read method
        $result = $controllerMock->read($this->collectionMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test read method with filters
     */
    public function testReadWithFilters(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create test filters
        $filters = ['name' => 'test_template'];
        
        // Mock the getData method to return test data
        $testData = [
            [
                'id' => 'template-123',
                'name' => 'test_template',
                'text' => 'Test template text',
                'channels' => ['sms'],
                'status' => 'new'
            ]
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the read method with filters
        $result = $controllerMock->read($this->collectionMock, $filters);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'read() should return the data from getData()');
    }

    /**
     * Test create method
     */
    public function testCreate(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Set up the Template mock to expect checkingIntegrityObject and touch calls
        $this->templateMock->expects($this->once())
            ->method('checkingIntegrityObject');
        
        $this->templateMock->expects($this->once())
            ->method('touch');
        
        // Mock the getData method to return test data
        $testData = [
            [
                'id' => 'template-123',
                'name' => 'test_template',
                'text' => 'Test template text',
                'channels' => ['sms'],
                'status' => 'new'
            ]
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the create method
        $result = $controllerMock->create($this->templateMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'create() should return the data from getData()');
    }

    /**
     * Test update method
     */
    public function testUpdate(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Set up the Template mock to expect checkingIntegrityObject call
        $this->templateMock->expects($this->once())
            ->method('checkingIntegrityObject');
        
        // Set up the Template mock to return changes
        $this->templateMock->method('getChanges')
            ->willReturn([
                'name' => 'updated_template',
                'text' => 'Updated template text'
            ]);
        
        // Mock the getData method to return test data
        $testData = [
            'success' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the update method
        $result = $controllerMock->update($this->templateMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'update() should return the data from getData()');
    }

    /**
     * Test delete method
     */
    public function testDelete(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Set up the Template mock to expect checkingIntegrityObject call
        $this->templateMock->expects($this->once())
            ->method('checkingIntegrityObject');
        
        // Mock the getData method to return test data
        $testData = [
            'success' => true
        ];
        
        // Create a partial mock of the controller to mock the getData method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['getData'])
            ->getMock();
        
        // Set up the mock to return test data
        $controllerMock->method('getData')
            ->willReturn($testData);
        
        // Call the delete method
        $result = $controllerMock->delete($this->templateMock);
        
        // Verify the result
        $this->assertEquals($testData, $result, 'delete() should return the data from getData()');
    }

    /**
     * Test filter method
     */
    public function testFilter(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create test filters
        $filters = [
            'id' => 'template-123',
            'name' => 'test_template',
            'text' => 'Test template text',
            'channel' => 'sms',
            'group_url' => 'https://example.com',
            'status' => 'new',
            'active' => 1
        ];
        
        // Call the filter method
        $result = $controller->filter($filters);
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controller, $result, 'filter() should return $this for method chaining');
        
        // Use reflection to check if filters are set correctly
        $reflection = new ReflectionClass(TemplateController::class);
        $filtersProperty = $reflection->getParentClass()->getProperty('filters');
        $filtersProperty->setAccessible(true);
        $controllerFilters = $filtersProperty->getValue($controller);
        
        // Verify the filters
        $this->assertEquals('template-123', $controllerFilters['id'], 'filters[id] should be set correctly');
        $this->assertEquals('test_template', $controllerFilters['name'], 'filters[name] should be set correctly');
        $this->assertEquals('Test template text', $controllerFilters['text'], 'filters[text] should be set correctly');
        $this->assertEquals('sms', $controllerFilters['channel'], 'filters[channel] should be set correctly');
        $this->assertEquals('https://example.com', $controllerFilters['group_url'], 'filters[group_url] should be set correctly');
        $this->assertEquals('new', $controllerFilters['status'], 'filters[status] should be set correctly');
        $this->assertEquals(1, $controllerFilters['active'], 'filters[active] should be set correctly');
    }

    /**
     * Test filterById method
     */
    public function testFilterById(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with id
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['id' => 'template-123'])
            ->willReturnSelf();
        
        // Call the filterById method
        $result = $controllerMock->filterById('template-123');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterById() should return $this for method chaining');
    }

    /**
     * Test filterByName method
     */
    public function testFilterByName(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with name
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['name' => 'test_template'])
            ->willReturnSelf();
        
        // Call the filterByName method
        $result = $controllerMock->filterByName('test_template');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByName() should return $this for method chaining');
    }

    /**
     * Test filterByText method
     */
    public function testFilterByText(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with text
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['text' => 'Test template text'])
            ->willReturnSelf();
        
        // Call the filterByText method
        $result = $controllerMock->filterByText('Test template text');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByText() should return $this for method chaining');
    }

    /**
     * Test filterByChannel method
     */
    public function testFilterByChannel(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with channel
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['channel' => 'sms'])
            ->willReturnSelf();
        
        // Call the filterByChannel method
        $result = $controllerMock->filterByChannel('sms');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByChannel() should return $this for method chaining');
    }

    /**
     * Test filterByGroupUrl method
     */
    public function testFilterByGroupUrl(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with group_url
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['group_url' => 'https://example.com'])
            ->willReturnSelf();
        
        // Call the filterByGroupUrl method
        $result = $controllerMock->filterByGroupUrl('https://example.com');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByGroupUrl() should return $this for method chaining');
    }

    /**
     * Test filterByStatus method
     */
    public function testFilterByStatus(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with status
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['status' => 'new'])
            ->willReturnSelf();
        
        // Call the filterByStatus method
        $result = $controllerMock->filterByStatus('new');
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByStatus() should return $this for method chaining');
    }

    /**
     * Test filterByActive method
     */
    public function testFilterByActive(): void
    {
        // Create a controller with the mock token
        $controller = new TemplateController($this->tokenMock);
        
        // Create a partial mock of the controller to mock the filter method
        $controllerMock = $this->getMockBuilder(TemplateController::class)
            ->setConstructorArgs([$this->tokenMock])
            ->onlyMethods(['filter'])
            ->getMock();
        
        // Set up the mock to expect filter call with active
        $controllerMock->expects($this->once())
            ->method('filter')
            ->with(['active' => true])
            ->willReturnSelf();
        
        // Call the filterByActive method
        $result = $controllerMock->filterByActive(true);
        
        // Verify the result is the controller instance (for method chaining)
        $this->assertSame($controllerMock, $result, 'filterByActive() should return $this for method chaining');
    }
}