<?php

namespace Imobis\Sdk\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response;
use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Sandbox;
use Imobis\Sdk\Entity\Token;
use Imobis\Sdk\Exceptions\ConnectionException;
use Imobis\Sdk\Request\HttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class HttpClientTest extends TestCase
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
    private $sandboxMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $guzzleClientMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test API key
        $this->testApiKey = Config::TEST_API_KEY;
        
        // Mock the Token class
        $this->tokenMock = $this->createMock(Token::class);
        $this->tokenMock->method('getToken')->willReturn($this->testApiKey);
        $this->tokenMock->method('validate')->willReturn(true);
        
        // Mock the Sandbox class
        $this->sandboxMock = $this->getMockBuilder(Sandbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sandboxMock->method('active')->willReturn(false);
        $this->sandboxMock->method('token')->willReturn(null);
        
        // Set up the Sandbox singleton instance
        $this->setStaticProperty(Sandbox::class, 'instances', [Sandbox::class => $this->sandboxMock]);
        
        // Mock the GuzzleHttp\Client class
        $this->guzzleClientMock = $this->getMockBuilder(GuzzleClient::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset the Sandbox singleton instance
        $this->resetSandboxInstance();
    }

    /**
     * Reset the Sandbox singleton instance
     */
    private function resetSandboxInstance(): void
    {
        $reflection = new ReflectionClass(Sandbox::class);
        $parentClass = $reflection->getParentClass();
        
        // Reset the instances property (from Singleton parent class)
        $instancesProperty = $parentClass->getProperty('instances');
        $instancesProperty->setAccessible(true);
        $instances = $instancesProperty->getValue();
        
        if (isset($instances[Sandbox::class])) {
            unset($instances[Sandbox::class]);
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
     * Get a protected property value using reflection
     */
    private function getProtectedProperty(object $object, string $propertyName)
    {
        $reflection = new ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        
        return $property->getValue($object);
    }
    
    /**
     * Create a mock StreamInterface that returns the given content
     */
    private function createMockStream(string $content): StreamInterface
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('__toString')->willReturn($content);
        $mockStream->method('getContents')->willReturn($content);
        
        return $mockStream;
    }

    /**
     * Test constructor initializes properties correctly
     */
    public function testConstructor(): void
    {
        $httpClient = new HttpClient($this->tokenMock);
        
        $this->assertInstanceOf(HttpClient::class, $httpClient, 'Constructor should return an HttpClient instance');
        
        // Check if token is set correctly
        $token = $this->getProtectedProperty($httpClient, 'token');
        $this->assertSame($this->tokenMock, $token, 'token property should be set to the token provided to constructor');
        
        // Check if sandbox is set correctly
        $sandbox = $this->getProtectedProperty($httpClient, 'sandbox');
        $this->assertSame($this->sandboxMock, $sandbox, 'sandbox property should be set to the Sandbox singleton instance');
        
        // Check if baseUrl is configured correctly
        $baseUrl = $this->getProtectedProperty($httpClient, 'baseUrl');
        $expectedBaseUrl = Config::BASE_URL . '/' . Config::API_VERSION;
        $this->assertEquals($expectedBaseUrl, $baseUrl, 'baseUrl should be set to the production URL by default');
        
        // Check if headers are configured correctly
        $headers = $this->getProtectedProperty($httpClient, 'headers');
        $this->assertArrayHasKey('Authorization', $headers, 'headers should contain Authorization key');
        $this->assertEquals('Token ' . $this->testApiKey, $headers['Authorization'], 'Authorization header should contain the token');
    }

    /**
     * Test getUrl method
     */
    public function testGetUrl(): void
    {
        $httpClient = new HttpClient($this->tokenMock);
        
        // Set a test route
        $this->setProtectedProperty($httpClient, 'route', 'test/route');
        
        // Call getUrl
        $url = $httpClient->getUrl();
        
        // Expected URL
        $expectedUrl = Config::BASE_URL . '/' . Config::API_VERSION . '/' . 'test/route';
        
        $this->assertEquals($expectedUrl, $url, 'getUrl() should return the correct URL');
        
        // Test with a route that starts with a slash
        $this->setProtectedProperty($httpClient, 'route', '/test/route2');
        
        // Call getUrl again
        $url = $httpClient->getUrl();
        
        // Expected URL (slash should be removed)
        $expectedUrl = Config::BASE_URL . '/' . Config::API_VERSION . '/' . 'test/route2';
        
        $this->assertEquals($expectedUrl, $url, 'getUrl() should handle routes that start with a slash');
    }

    /**
     * Test getUrl method with different routes
     */
    public function testGetUrlWithDifferentRoutes(): void
    {
        $httpClient = new HttpClient($this->tokenMock);
        
        // Test with a simple route
        $this->setProtectedProperty($httpClient, 'route', 'test/route');
        $url = $httpClient->getUrl();
        $expectedUrl = Config::BASE_URL . '/' . Config::API_VERSION . '/' . 'test/route';
        $this->assertEquals($expectedUrl, $url, 'getUrl() should return the correct URL for a simple route');
        
        // Test with a route that starts with a slash
        $this->setProtectedProperty($httpClient, 'route', '/test/route2');
        $url = $httpClient->getUrl();
        $expectedUrl = Config::BASE_URL . '/' . Config::API_VERSION . '/' . 'test/route2';
        $this->assertEquals($expectedUrl, $url, 'getUrl() should handle routes that start with a slash');
    }
    
    /**
     * Test getResponse method with valid JSON response
     */
    public function testGetResponseWithValidJsonResponse(): void
    {
        // Create a mock response
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);
        
        // Create a mock stream with JSON content
        $jsonContent = json_encode(['success' => true]);
        $mockStream = $this->createMockStream($jsonContent);
        $mockResponse->method('getBody')->willReturn($mockStream);
        
        // Create an HttpClient instance
        $httpClient = new HttpClient($this->tokenMock);
        
        // Set the response property using reflection
        $this->setProtectedProperty($httpClient, 'response', $mockResponse);
        
        // Call getResponse
        $result = $httpClient->getResponse();
        
        // Verify the result
        $this->assertIsArray($result, 'getResponse() should return an array');
        $this->assertEquals(200, $result['code'], 'Response code should be 200');
        $this->assertEquals(['Content-Type' => ['application/json']], $result['headers'], 'Response headers should be included');
        $this->assertEquals(['success' => true], $result['body'], 'Response body should be decoded JSON');
    }

    /**
     * Test getResponse method with error status code
     */
    public function testGetResponseWithErrorStatusCode(): void
    {
        // Create a mock response with an error status code
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn(400);
        $mockResponse->method('getHeaders')->willReturn(['Content-Type' => ['application/json']]);
        
        // Create a mock stream with JSON error content
        $errorContent = json_encode(['error' => 'Bad Request']);
        $mockStream = $this->createMockStream($errorContent);
        $mockResponse->method('getBody')->willReturn($mockStream);
        
        // Create an HttpClient instance
        $httpClient = new HttpClient($this->tokenMock);
        
        // Set the response property using reflection
        $this->setProtectedProperty($httpClient, 'response', $mockResponse);
        
        // Call getResponse
        $result = $httpClient->getResponse();
        
        // Verify the result
        $this->assertIsArray($result, 'getResponse() should return an array');
        $this->assertEquals(400, $result['code'], 'Response code should be 400');
        $this->assertEquals(['Content-Type' => ['application/json']], $result['headers'], 'Response headers should be included');
        $this->assertEquals(['error' => 'Bad Request'], $result['body'], 'Response body should be decoded JSON');
    }

    /**
     * Test getResponse method with non-JSON response
     */
    public function testGetResponseWithNonJsonResponse(): void
    {
        // Create a mock response with non-JSON content type
        $mockResponse = $this->createMock(Response::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getHeaders')->willReturn(['Content-Type' => ['text/plain']]);
        
        // Create a mock stream with plain text content
        $plainTextContent = 'Plain text response';
        $mockStream = $this->createMockStream($plainTextContent);
        $mockResponse->method('getBody')->willReturn($mockStream);
        
        // Create an HttpClient instance
        $httpClient = new HttpClient($this->tokenMock);
        
        // Set the response property using reflection
        $this->setProtectedProperty($httpClient, 'response', $mockResponse);
        
        // Call getResponse
        $result = $httpClient->getResponse();
        
        // Verify the result
        $this->assertIsArray($result, 'getResponse() should return an array');
        $this->assertEquals(200, $result['code'], 'Response code should be 200');
        $this->assertEquals(['Content-Type' => ['text/plain']], $result['headers'], 'Response headers should be included');
        $this->assertEquals($plainTextContent, $result['body'], 'Response body should be plain text');
    }

    /**
     * Test getResponse method with invalid response
     */
    public function testGetResponseWithInvalidResponse(): void
    {
        // Create an HttpClient instance
        $httpClient = new HttpClient($this->tokenMock);
        
        // Initialize the route property to avoid uninitialized string offset error
        $this->setProtectedProperty($httpClient, 'route', 'test/route');
        
        // Set the response property to null
        $this->setProtectedProperty($httpClient, 'response', null);
        
        // Expect a ConnectionException
        $this->expectException(ConnectionException::class);
        
        // Call getResponse
        $httpClient->getResponse();
    }

    /**
     * Set a protected property value using reflection
     */
    private function setProtectedProperty(object $object, string $propertyName, $value): void
    {
        $reflection = new ReflectionClass(get_class($object));
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}