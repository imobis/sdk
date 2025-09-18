<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Entity\Token;
use Nexus\Message\Sdk\Exceptions\HttpInvalidArgumentException;
use Nexus\Message\Sdk\Request\Router;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once 'vendor/autoload.php';

/**
 * Test class for Router
 */
class RouterTest extends TestCase
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
    private $httpClientMock;

    /**
     * Test subclass of Router that overrides the HttpClient creation
     */
    private $testRouter;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test API key
        $this->testApiKey = Config::TEST_API_KEY;
        
        // Mock the Token class
        $this->tokenMock = $this->createMock(Token::class);
        $this->tokenMock->method('getToken')->willReturn($this->testApiKey);
        $this->tokenMock->method('validate')->willReturn(true);
        
        // Get access to the protected ENTITIES constant
        $reflection = new ReflectionClass(Router::class);
        $constants = $reflection->getConstants();
        $this->entities = $constants['ENTITIES'];
    }

    /**
     * Test getResponse method with valid entity and action
     */
    public function testGetResponseWithValidEntity(): void
    {
        // Skip this test if the entity or action doesn't exist in ENTITIES
        if (!isset($this->entities[\Nexus\Message\Sdk\Entity\Balance::class]['read'])) {
            $this->markTestSkipped('Balance entity or read action not found in ENTITIES');
        }
        
        // Call getResponse with a valid entity and action
        $result = Router::getResponse(
            $this->tokenMock,
            \Nexus\Message\Sdk\Entity\Balance::class,
            'read'
        );
        
        // Verify the result is an array with expected structure
        $this->assertIsArray($result, 'getResponse() should return an array');
        $this->assertArrayHasKey('code', $result, 'Response should have a code key');
        $this->assertArrayHasKey('body', $result, 'Response should have a body key');
    }

    /**
     * Test getResponse method with invalid entity
     */
    public function testGetResponseWithInvalidEntity(): void
    {
        // Call getResponse with an invalid entity
        $result = Router::getResponse(
            $this->tokenMock,
            'InvalidEntity',
            'read'
        );
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'getResponse() should return an empty array for invalid entity');
    }

    /**
     * Test getResponse method with invalid action
     */
    public function testGetResponseWithInvalidAction(): void
    {
        // Call getResponse with an invalid action
        $result = Router::getResponse(
            $this->tokenMock,
            \Nexus\Message\Sdk\Entity\Template::class,
            'invalidAction'
        );
        
        // Verify the result is an empty array
        $this->assertEquals([], $result, 'getResponse() should return an empty array for invalid action');
    }

    /**
     * Test that getResponse method handles options correctly
     */
    public function testGetResponseHandlesOptions(): void
    {
        // Skip this test if the entity or action doesn't exist in ENTITIES
        if (!isset($this->entities[\Nexus\Message\Sdk\Core\Message::class]['channel']['options'])) {
            $this->markTestSkipped('Message entity, channel action, or options not found in ENTITIES');
        }
        
        // Verify that the options exist in the ENTITIES constant
        $this->assertArrayHasKey('options', $this->entities[\Nexus\Message\Sdk\Core\Message::class]['channel'], 'Channel action should have options');
        $this->assertArrayHasKey('sms', $this->entities[\Nexus\Message\Sdk\Core\Message::class]['channel']['options'], 'Options should include sms');
        
        // This is a structural test, not a functional test
        $this->assertTrue(true, 'Options structure verified in ENTITIES constant');
    }

    /**
     * Test parseResponse method with successful response in DATA_MODE
     */
    public function testParseResponseWithSuccessfulResponseInDataMode(): void
    {
        // Skip this test if the entity or action doesn't exist in ENTITIES
        if (!isset($this->entities[\Nexus\Message\Sdk\Entity\Template::class]['read'])) {
            $this->markTestSkipped('Template entity or read action not found in ENTITIES');
        }
        
        // Create a test response
        $testResponse = [
            'code' => 200,
            'body' => [
                'data' => [
                    'id' => 'template-123',
                    'name' => 'test_template',
                    'text' => 'Test template text'
                ]
            ]
        ];
        
        // Call parseResponse
        $result = Router::parseResponse(
            $testResponse,
            \Nexus\Message\Sdk\Entity\Template::class,
            'read',
            Config::DATA_MODE
        );
        
        // Verify the result
        $this->assertIsArray($result, 'parseResponse() should return an array in DATA_MODE');
        $this->assertArrayHasKey('id', $result, 'Result should contain id key');
        $this->assertArrayHasKey('name', $result, 'Result should contain name key');
        $this->assertArrayHasKey('text', $result, 'Result should contain text key');
        $this->assertEquals('template-123', $result['id'], 'id should match the input');
        $this->assertEquals('test_template', $result['name'], 'name should match the input');
        $this->assertEquals('Test template text', $result['text'], 'text should match the input');
    }

    /**
     * Test parseResponse method with error response
     */
    public function testParseResponseWithErrorResponse(): void
    {
        // Create a test error response
        $testResponse = [
            'code' => 400,
            'body' => [
                'desc' => 'Bad Request'
            ]
        ];
        
        // Expect an HttpInvalidArgumentException
        $this->expectException(HttpInvalidArgumentException::class);
        
        // Call parseResponse
        Router::parseResponse(
            $testResponse,
            \Nexus\Message\Sdk\Entity\Template::class,
            'read',
            Config::DATA_MODE
        );
    }

    /**
     * Test parseResponse method with collection mode
     */
    public function testParseResponseWithCollectionMode(): void
    {
        // Skip this test if the entity or action doesn't exist in ENTITIES
        if (!isset($this->entities[\Nexus\Message\Sdk\Entity\Template::class]['read'])) {
            $this->markTestSkipped('Template entity or read action not found in ENTITIES');
        }
        
        // Create a mock Template class that can be instantiated with an array
        $templateMock = $this->getMockBuilder(\Nexus\Message\Sdk\Entity\Template::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Create a test response with multiple items
        $testResponse = [
            'code' => 200,
            'body' => [
                'data' => [
                    [
                        'id' => 'template-123',
                        'name' => 'test_template_1',
                        'text' => 'Test template text 1'
                    ],
                    [
                        'id' => 'template-456',
                        'name' => 'test_template_2',
                        'text' => 'Test template text 2'
                    ]
                ]
            ]
        ];
        
        // Create a collection with the mock Template class
        $collection = $this->getMockBuilder(Collection::class)
            ->setConstructorArgs([\Nexus\Message\Sdk\Entity\Template::class])
            ->getMock();
        
        // Set up the collection mock to expect addObject calls
        $collection->expects($this->atLeastOnce())
            ->method('addObject')
            ->willReturn(true);
        
        // Set up the collection mock to expect last call
        $collection->expects($this->atLeastOnce())
            ->method('last')
            ->willReturn($templateMock);
        
        // Call parseResponse with collection mode
        $result = Router::parseResponse(
            $testResponse,
            \Nexus\Message\Sdk\Entity\Template::class,
            'read',
            Config::COLLECTION_MODE,
            $collection
        );
        
        // Verify the result is the collection
        $this->assertSame($collection, $result, 'parseResponse() should return the collection');
    }

    /**
     * Test getValidationRules method with valid model and action
     */
    public function testGetValidationRulesWithValidModelAndAction(): void
    {
        // Get validation rules for Template create
        $rules = Router::getValidationRules(\Nexus\Message\Sdk\Entity\Template::class, 'create');
        
        // Verify the rules
        $this->assertIsArray($rules, 'getValidationRules() should return an array');
        $this->assertArrayHasKey('name', $rules, 'Rules should contain name key');
        $this->assertArrayHasKey('text', $rules, 'Rules should contain text key');
        $this->assertArrayHasKey('channels', $rules, 'Rules should contain channels key');
    }

    /**
     * Test getValidationRules method with invalid model
     */
    public function testGetValidationRulesWithInvalidModel(): void
    {
        // Get validation rules for invalid model
        $rules = Router::getValidationRules('InvalidModel', 'create');
        
        // Verify the rules are empty
        $this->assertEquals([], $rules, 'getValidationRules() should return an empty array for invalid model');
    }

    /**
     * Test getValidationRules method with invalid action
     */
    public function testGetValidationRulesWithInvalidAction(): void
    {
        // Get validation rules for invalid action
        $rules = Router::getValidationRules(\Nexus\Message\Sdk\Entity\Template::class, 'invalidAction');
        
        // Verify the rules are empty
        $this->assertEquals([], $rules, 'getValidationRules() should return an empty array for invalid action');
    }
}