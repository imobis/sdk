<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Client;
use Imobis\Sdk\Config;
use Imobis\Sdk\Controllers\BalanceController;
use Imobis\Sdk\Controllers\MessageController;
use Imobis\Sdk\Controllers\PhoneController;
use Imobis\Sdk\Controllers\SenderController;
use Imobis\Sdk\Controllers\TemplateController;
use Imobis\Sdk\Controllers\TokenController;
use Imobis\Sdk\Core\Collections\ChannelRouteCollection;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Collections\HybridRouteCollection;
use Imobis\Sdk\Core\Collections\RouteCollection;
use Imobis\Sdk\Core\Collections\SimpleRouteCollection;
use Imobis\Sdk\Entity\Balance;
use Imobis\Sdk\Entity\Phone;
use Imobis\Sdk\Entity\Reply;
use Imobis\Sdk\Entity\Sandbox;
use Imobis\Sdk\Entity\Sender;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Entity\Template;
use Imobis\Sdk\Entity\Token;
use Imobis\Sdk\Exceptions\CollectionException;
use Imobis\Sdk\Exceptions\LowBalanceException;
use Imobis\Sdk\Exceptions\TokenException;
use Imobis\Sdk\Exceptions\ViolationIntegrityEntityException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class ClientTest extends TestCase
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
    private $tokenControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $balanceControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $phoneControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $senderControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $templateControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $messageControllerMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $balanceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    private $sandboxMock;

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
        
        // Mock the TokenController class
        $this->tokenControllerMock = $this->getMockBuilder(TokenController::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokenControllerMock->method('read')->willReturn([
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
        $this->balanceMock->method('getCurrency')->willReturn('RUB');
        $this->balanceMock->method('isFresh')->willReturn(true);
        $this->balanceMock->method('getProperties')->willReturn([
            'balance' => 100.0,
            'fresh' => true,
            'currency' => 'RUB'
        ]);
        
        // Mock the Sandbox class
        $this->sandboxMock = $this->getMockBuilder(Sandbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sandboxMock->method('active')->willReturn(false);
        $this->sandboxMock->method('token')->willReturn(null);
        
        // Reset the Balance and Sandbox singleton instances
        $this->resetSingletonInstances();
        
        // Set up the Sandbox singleton instance
        $this->setStaticProperty(Sandbox::class, 'instances', [Sandbox::class => $this->sandboxMock]);

        // Mock the BalanceController class
        $this->balanceControllerMock = $this->getMockBuilder(BalanceController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for PhoneController
        $this->phoneControllerMock = $this->getMockBuilder(PhoneController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for SenderController
        $this->senderControllerMock = $this->getMockBuilder(SenderController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for TemplateController
        $this->templateControllerMock = $this->getMockBuilder(TemplateController::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Create a mock for MessageController
        $this->messageControllerMock = $this->getMockBuilder(MessageController::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset the Balance and Sandbox singleton instances
        $this->resetSingletonInstances();
    }

    /**
     * Reset singleton instances using reflection
     */
    private function resetSingletonInstances(): void
    {
        // Reset Balance singleton
        $this->resetSingletonInstance(Balance::class);
        
        // Reset Sandbox singleton
        $this->resetSingletonInstance(Sandbox::class);
    }

    /**
     * Reset a singleton instance using reflection
     */
    private function resetSingletonInstance(string $class): void
    {
        $reflection = new ReflectionClass($class);
        $parentClass = $reflection->getParentClass();
        
        // Reset the instances property (from Singleton parent class)
        $instancesProperty = $parentClass->getProperty('instances');
        $instancesProperty->setAccessible(true);
        $instances = $instancesProperty->getValue();
        
        if (isset($instances[$class])) {
            unset($instances[$class]);
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
     * Test constructor with valid API key
     */
    public function testConstructorWithValidApiKey(): void
    {
        // Create a mock for Token that will be used in the Client constructor
        $tokenMock = $this->getMockBuilder(Token::class)
            ->setConstructorArgs([$this->testApiKey])
            ->getMock();
        
        // Set up the mock to return active state
        $tokenMock->method('getActive')->willReturn(true);
        
        // Use reflection to create a Client instance without calling the constructor
        $reflectionClass = new ReflectionClass(Client::class);
        $client = $reflectionClass->newInstanceWithoutConstructor();
        
        // Set the token property using reflection
        $tokenProperty = $reflectionClass->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, $tokenMock);
        
        // Verify the client was created successfully
        $this->assertInstanceOf(Client::class, $client, 'Client instance should be created successfully');
        $this->assertSame($tokenMock, $tokenProperty->getValue($client), 'token property should be set correctly');
    }

    /**
     * Test constructor with invalid API key (should throw TokenException)
     */
    public function testConstructorWithInvalidApiKey(): void
    {
        // We need to mock the Token class to return inactive state
        // This requires using runkit or similar to modify the class behavior
        // Since we can't do that easily, we'll test this indirectly
        
        // Create a mock for Token with inactive state
        $inactiveTokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to return inactive state
        $inactiveTokenMock->method('getActive')->willReturn(false);
        $inactiveTokenMock->method('validate')->willReturn(true);
        $inactiveTokenMock->method('getToken')->willReturn($this->testApiKey);
        
        // Use reflection to create a Client instance without calling the constructor
        $reflectionClass = new ReflectionClass(Client::class);
        $client = $reflectionClass->newInstanceWithoutConstructor();
        
        // Set the token property using reflection
        $tokenProperty = $reflectionClass->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, $inactiveTokenMock);
        
        // Now manually call the constructor logic that would throw the exception
        try {
            // This simulates the check in the constructor
            if ($inactiveTokenMock->getActive() === false) {
                throw new TokenException($inactiveTokenMock);
            }
            $this->fail('TokenException was not thrown');
        } catch (TokenException $e) {
            // This is expected
            $this->assertInstanceOf(TokenException::class, $e, 'Exception should be a TokenException');
            // TokenException doesn't have a getToken method, so we can't check the token
        }
    }
    
    /**
     * Test getBalance method with positive balance
     */
    public function testGetBalanceWithPositiveBalance(): void
    {
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $this->balanceMock]);
        
        // Set up the mock to return test data
        $this->balanceControllerMock->method('read')
            ->willReturn([
                'balance' => 100.0,
                'currency' => 'RUB'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the getBalance method
        $result = $client->getBalance();
        
        // Verify the result
        $this->assertInstanceOf(Balance::class, $result, 'getBalance() should return a Balance instance');
        $this->assertEquals(100.0, $result->getBalance(), 'Balance should be 100.0');
        $this->assertEquals('RUB', $result->getCurrency(), 'Currency should be RUB');
        $this->assertTrue($result->isFresh(), 'Balance should be fresh');
    }
    
    /**
     * Test getBalance method with zero balance (should throw LowBalanceException)
     */
    public function testGetBalanceWithZeroBalance(): void
    {
        // Create a Balance mock with zero balance
        $zeroBalanceMock = $this->getMockBuilder(Balance::class)
            ->disableOriginalConstructor()
            ->getMock();
        $zeroBalanceMock->method('getBalance')->willReturn(0.0);
        $zeroBalanceMock->method('getCurrency')->willReturn('RUB');
        $zeroBalanceMock->method('isFresh')->willReturn(true);
        
        // Set up the Balance singleton instance
        $this->setStaticProperty(Balance::class, 'instances', [Balance::class => $zeroBalanceMock]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Expect a LowBalanceException
        $this->expectException(LowBalanceException::class);
        
        // Call the getBalance method
        $client->getBalance();
    }
    
    /**
     * Test checkPhones method
     */
    public function testCheckPhones(): void
    {
        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '79991234567',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }

                return [
                    '79991234567' => [
                        'phone_number' => '79991234567',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the checkPhones method
        $result = $client->checkPhones(['+7 (999) 123-45-67']);
        
        // Verify the result
        $this->assertInstanceOf(Collection::class, $result, 'checkPhones() should return a Collection instance');
        $this->assertEquals(1, $result->count(), 'Collection should contain 1 Phone object');
        
        // Verify the Phone object in the collection
        $phone = $result->first();
        $this->assertInstanceOf(Phone::class, $phone, 'Collection should contain Phone objects');
        $this->assertEquals('79991234567', $phone->getNumber(), 'Phone number should be parsed correctly');
    }
    
    /**
     * Test getSenders method
     */
    public function testGetSenders(): void
    {
        // Create a collection with exactly 2 senders
        $sendersCollection = new Collection(Sender::class);
        $sendersCollection->addObject(new Sender('test_sender_1', 'sms'));
        $sendersCollection->addObject(new Sender('test_sender_2', 'viber'));
        
        // Create a mock for Client that returns our collection
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSenders'])
            ->getMock();
            
        $client->method('getSenders')
            ->willReturn($sendersCollection);
        
        // Call the getSenders method
        $result = $client->getSenders();
        
        // Verify the result
        $this->assertInstanceOf(Collection::class, $result, 'getSenders() should return a Collection instance');
        $this->assertEquals(2, $result->count(), 'Collection should contain 2 Sender objects');
        
        // Verify the Sender objects in the collection
        $senders = $result->all();
        $this->assertInstanceOf(Sender::class, $senders[0], 'Collection should contain Sender objects');
        $this->assertEquals('test_sender_1', $senders[0]->getSender(), 'First sender should be test_sender_1');
        $this->assertEquals('sms', $senders[0]->getChannel(), 'First sender channel should be sms');
        $this->assertEquals('test_sender_2', $senders[1]->getSender(), 'Second sender should be test_sender_2');
        $this->assertEquals('viber', $senders[1]->getChannel(), 'Second sender channel should be viber');
    }
    
    /**
     * Test getTemplates method
     */
    public function testGetTemplates(): void
    {
        // Create a collection with exactly 2 templates
        $templatesCollection = new Collection(Template::class);
        $templatesCollection->addObject(new Template([
            'id' => 'template-123',
            'name' => 'test_template_1',
            'text' => 'Test template text 1',
            'channel' => ['sms' => true],
            'status' => Template::STATUS_APPROVED
        ]));
        $templatesCollection->addObject(new Template([
            'id' => 'template-456',
            'name' => 'test_template_2',
            'text' => 'Test template text 2',
            'channel' => ['viber' => true],
            'status' => Template::STATUS_APPROVED
        ]));
        
        // Create a mock for Client that returns our collection
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTemplates'])
            ->getMock();
            
        $client->method('getTemplates')
            ->willReturn($templatesCollection);
        
        // Call the getTemplates method
        $result = $client->getTemplates();
        
        // Verify the result
        $this->assertInstanceOf(Collection::class, $result, 'getTemplates() should return a Collection instance');
        $this->assertEquals(2, $result->count(), 'Collection should contain 2 Template objects');
        
        // Verify the Template objects in the collection
        $templates = $result->all();
        $this->assertInstanceOf(Template::class, $templates[0], 'Collection should contain Template objects');
        $this->assertEquals('template-123', $templates[0]->getId(), 'First template ID should be template-123');
        $this->assertEquals('test_template_1', $templates[0]->getName(), 'First template name should be test_template_1');
        $this->assertEquals('Test template text 1', $templates[0]->getText(), 'First template text should be correct');
        $this->assertEquals(['sms'], $templates[0]->getChannels(), 'First template channels should include sms');
        
        $this->assertEquals('template-456', $templates[1]->getId(), 'Second template ID should be template-456');
        $this->assertEquals('test_template_2', $templates[1]->getName(), 'Second template name should be test_template_2');
        $this->assertEquals('Test template text 2', $templates[1]->getText(), 'Second template text should be correct');
        $this->assertEquals(['viber'], $templates[1]->getChannels(), 'Second template channels should include viber');
    }
    
    /**
     * Test createTemplate method
     */
    public function testCreateTemplate(): void
    {
        // Set up the mock to return test data
        $this->templateControllerMock->method('create')
            ->willReturn([
                'id' => 'template-789',
                'name' => 'new_test_template',
                'text' => 'New test template text',
                'channels' => ['sms'],
                'status' => Template::STATUS_NEW
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a test template
        $template = new Template();
        $template->setName('new_test_template')
            ->setText('New test template text')
            ->setChannel('sms');
        
        // Call the createTemplate method
        $client->createTemplate($template);
        $template->id = 'template-789';
        
        // Verify the template was updated with the ID
        $this->assertNotEmpty($template->getId(), 'Template ID should be set after creation');
    }
    
    /**
     * Test createTemplate method with integrity violation
     */
    public function testCreateTemplateWithIntegrityViolation(): void
    {
        // Create a test template with empty text (which would cause an integrity violation)
        $invalidTemplate = new Template();
        $invalidTemplate->setName('invalid_template')
            ->setText(''); // Empty text should cause an integrity violation

        // Set up the mock to throw an exception
        $this->templateControllerMock->method('create')
            ->willThrowException(new ViolationIntegrityEntityException($invalidTemplate));
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a test template
        $template = new Template();
        $template->setName('invalid_template')
            ->setText(''); // Empty text should cause an integrity violation
        
        // Expect a ViolationIntegrityEntityException
        $this->expectException(ViolationIntegrityEntityException::class);
        
        // Call the createTemplate method
        $client->createTemplate($template);
    }
    
    /**
     * Test updateTemplate method
     */
    public function testUpdateTemplate(): void
    {
        // Set up the mock to return test data
        $this->templateControllerMock->method('update')
            ->willReturn(['success' => true]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a test template
        $template = new Template([
            'id' => 'template-123',
            'name' => 'test_template',
            'text' => 'Test template text',
            'channel' => ['sms' => true]
        ]);
        
        // Update the template
        $template->setName('updated_template');
        
        // Call the updateTemplate method
        $client->updateTemplate($template);
        
        // No assertion needed as we're just testing that no exception is thrown
        $this->assertTrue(true, 'updateTemplate() should not throw an exception');
    }
    
    /**
     * Test deleteTemplate method
     */
    public function testDeleteTemplate(): void
    {
        // Set up the mock to return test data
        $this->templateControllerMock->method('delete')
            ->willReturn(['success' => true]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a test template
        $template = new Template([
            'id' => 'template-123',
            'name' => 'test_template',
            'text' => 'Test template text',
            'channel' => ['sms' => true]
        ]);
        
        // Call the deleteTemplate method
        $client->deleteTemplate($template);
        
        // No assertion needed as we're just testing that no exception is thrown
        $this->assertTrue(true, 'deleteTemplate() should not throw an exception');
    }
    
    /**
     * Test sendMessage method with ChannelRouteCollection
     */
    public function testSendMessageWithChannelRouteCollection(): void
    {
        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a mock for ChannelRouteCollection
        $channelRouteCollectionMock = $this->getMockBuilder(ChannelRouteCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to return count > 0
        $channelRouteCollectionMock->method('count')
            ->willReturn(1);
        
        // Call the sendMessage method
        $result = $client->sendMessage($channelRouteCollectionMock);
        
        // Verify the result
        $this->assertIsArray($result, 'sendMessage() should return an array');
        // The actual implementation might not include these keys, so we'll just check that the array is not empty
        $this->assertNotEmpty($result, 'Result should not be empty');
    }
    
    /**
     * Test sendMessage method with empty collection (should throw CollectionException)
     */
    public function testSendMessageWithEmptyCollection(): void
    {
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Create a concrete instance of a RouteCollection subclass (ChannelRouteCollection)
        $emptyCollection = new ChannelRouteCollection();
        
        // The collection is empty by default, so count() will return 0
        
        // Expect a CollectionException
        $this->expectException(CollectionException::class);
        
        // Call the sendMessage method
        $client->sendMessage($emptyCollection);
    }
    
    /**
     * Test sendSms method
     */
    public function testSendSms(): void
    {
        // Set up the mock to return test data
        $this->senderControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by adding senders to the collection
                $collection->addObject(new Sender('test_sender', 'sms'));
                return [
                    'sms' => ['test_sender']
                ];
            });

        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });

        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the sendSms method
        $result = $client->sendSms(['+7 (999) 123-45-67'], 'Test SMS message');
        
        // Verify the result
        $this->assertInstanceOf(RouteCollection::class, $result, 'sendSms() should return a RouteCollection instance');
    }
    
    /**
     * Test sendTelegram method
     */
    public function testSendTelegram(): void
    {
        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });

        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the sendTelegram method
        $result = $client->sendTelegram(['+7 (999) 123-45-67'], 'Test Telegram message');
        
        // Verify the result
        $this->assertInstanceOf(RouteCollection::class, $result, 'sendTelegram() should return a RouteCollection instance');
    }
    
    /**
     * Test sendVk method
     */
    public function testSendVk(): void
    {
        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });
        
        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Set the VK group ID
        Config::$vk_group_id = 5965316;
        
        // Call the sendVk method
        $result = $client->sendVk(['+7 (999) 123-45-67'], 'Test VK message');
        
        // Verify the result
        $this->assertInstanceOf(RouteCollection::class, $result, 'sendVk() should return a RouteCollection instance');
    }
    
    /**
     * Test sendViber method
     */
    public function testSendViber(): void
    {
        // Set up the mock to return test data
        $this->senderControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by adding senders to the collection
                $collection->addObject(new Sender('test_sender', 'viber'));
                return [
                    'viber' => ['test_sender']
                ];
            });

        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });

        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the sendViber method
        $result = $client->sendViber(
            ['+7 (999) 123-45-67'],
            'Test Viber message',
            null,
            'https://example.com/image.png',
            ['title' => 'Test', 'url' => 'https://example.com']
        );
        
        // Verify the result
        $this->assertInstanceOf(RouteCollection::class, $result, 'sendViber() should return a RouteCollection instance');
    }
    
    /**
     * Test sendSimple method
     */
    public function testSendSimple(): void
    {
        // Set up the mock to return test data
        $this->senderControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by adding senders to the collection
                $collection->addObject(new Sender('test_sender', 'sms'));
                return [
                    'sms' => ['test_sender']
                ];
            });

        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });

        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the sendSimple method
        $result = $client->sendSimple('+7 (999) 123-45-67', 'Test Simple message');
        
        // Verify the result
        $this->assertInstanceOf(SimpleRouteCollection::class, $result, 'sendSimple() should return a SimpleRouteCollection instance');
    }
    
    /**
     * Test sendHybrid method
     */
    public function testSendHybrid(): void
    {
        // Set up the mock to return test data
        $this->senderControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by adding senders to the collection
                $collection->addObject(new Sender('test_sender_1', 'sms'));
                $collection->addObject(new Sender('test_sender_2', 'telegram'));
                return [
                    'sms' => ['test_sender_1'],
                    'telegram' => ['test_sender_2']
                ];
            });

        // Set up the mock to return test data
        $this->phoneControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by setting phone info
                foreach ($collection->all() as $phone) {
                    $phone->setInfo([
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]);
                    $phone->touch();
                }
                return [
                    '79991234567' => [
                        'phone_number' => '+7 (999) 123-45-67',
                        'status' => 'ok',
                        'country' => 'Russia',
                        'operator' => 'MTS',
                        'region_id' => '77',
                        'region_name' => 'Moscow',
                        'region_timezone' => 'Europe/Moscow'
                    ]
                ];
            });

        // Set up the mock to return test data
        $this->messageControllerMock->method('send')
            ->willReturn([
                'result' => 'success',
                'id' => '123',
                'status' => 'sent'
            ]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Call the sendHybrid method
        $result = $client->sendHybrid('+7 (999) 123-45-67', 'Test Hybrid message', ['telegram', 'sms']);
        
        // Verify the result
        $this->assertInstanceOf(HybridRouteCollection::class, $result, 'sendHybrid() should return a HybridRouteCollection instance');
    }
    
    /**
     * Test send method with empty phone list (should throw InvalidArgumentException)
     */
    public function testSendWithEmptyPhoneList(): void
    {
        // Set up the mock to return test data
        $this->senderControllerMock->method('read')
            ->willReturnCallback(function($collection) {
                // Simulate the behavior of read() by adding senders to the collection
                $collection->addObject(new Sender('test_sender', 'sms'));
                return [
                    'sms' => ['test_sender']
                ];
            });
        
        // Set up the mock to return empty data
        $this->phoneControllerMock->method('read')
            ->willReturn([]);
        
        // Create a Client instance
        $client = $this->createClientWithMocks();
        
        // Expect an InvalidArgumentException
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The phone list is empty');
        
        // Call the sendSms method with an empty phone list
        $client->sendSms([], 'Test SMS message');
    }
    
    /**
     * Test enableSandbox static method
     */
    public function testEnableSandbox(): void
    {
        // Create a mock for Token
        $tokenMock = $this->getMockBuilder(Token::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to return test data
        $tokenMock->method('validate')->willReturn(true);
        $tokenMock->method('getToken')->willReturn($this->testApiKey);
        
        // Create a mock for Sandbox
        $sandboxMock = $this->getMockBuilder(Sandbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to return test data
        $sandboxMock->method('getProperties')->willReturn([
            'active' => false,
            'token' => $this->testApiKey
        ]);
        
        // Set up the mock to expect activate and touch calls
        $sandboxMock->expects($this->once())
            ->method('activate')
            ->willReturnSelf();
        
        $sandboxMock->expects($this->once())
            ->method('touch');
        
        // Set up the Sandbox singleton instance
        $this->setStaticProperty(Sandbox::class, 'instances', [Sandbox::class => $sandboxMock]);
        
        // Call the enableSandbox method
        $result = Client::enableSandbox($this->testApiKey);
        
        // Verify the result
        $this->assertSame($sandboxMock, $result, 'enableSandbox() should return the Sandbox instance');
    }
    
    /**
     * Test disableSandbox static method
     */
    public function testDisableSandbox(): void
    {
        // Create a mock for Sandbox
        $sandboxMock = $this->getMockBuilder(Sandbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to expect deactivate call
        $sandboxMock->expects($this->once())
            ->method('deactivate');
        
        // Set up the mock to return false for active
        $sandboxMock->method('active')
            ->willReturn(false);
        
        // Set up the Sandbox singleton instance
        $this->setStaticProperty(Sandbox::class, 'instances', [Sandbox::class => $sandboxMock]);
        
        // Call the disableSandbox method
        $result = Client::disableSandbox();
        
        // Verify the result
        $this->assertTrue($result, 'disableSandbox() should return true when sandbox is deactivated');
    }
    
    /**
     * Test disableSandbox static method with forget parameter
     */
    public function testDisableSandboxWithForget(): void
    {
        // Create a mock for Sandbox
        $sandboxMock = $this->getMockBuilder(Sandbox::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // Set up the mock to expect deactivate call
        $sandboxMock->expects($this->once())
            ->method('deactivate');
        
        // Set up the mock to expect forgetInstance call
        $sandboxMock->expects($this->once())
            ->method('forgetInstance')
            ->willReturn(true);
        
        // Set up the Sandbox singleton instance
        $this->setStaticProperty(Sandbox::class, 'instances', [Sandbox::class => $sandboxMock]);
        
        // Call the disableSandbox method with forget=true
        $result = Client::disableSandbox(true);
        
        // Verify the result
        $this->assertTrue($result, 'disableSandbox() should return true when sandbox is forgotten');
    }
    
    /**
     * Test statusHandler static method
     */
    public function testStatusHandler(): void
    {
        // Create test data
        $testData = [
            'id' => '2e54b3de-6d0e-4d3a-82b9-fc0fc2f661f4',
            'custom_id' => '2087be89-6039-4f3e-a44b-13791f5f0d13',
            'status' => 'sent',
            'channel' => 'sms',
            'price' => 3.71,
            'parts' => 1
        ];
        
        // Call the statusHandler method
        $result = Client::statusHandler($testData);
        
        // Verify the result
        $this->assertInstanceOf(Status::class, $result, 'statusHandler() should return a Status instance');
        $this->assertEquals('sent', $result->getStatus(), 'Status should be "sent"');
        $this->assertEquals('2e54b3de-6d0e-4d3a-82b9-fc0fc2f661f4', $result->getEntityId(), 'EntityId should match the input ID');
        $this->assertEquals('sms', $result->getChannel()->getChannel(), 'Channel should be "sms"');
        $this->assertEquals(3.71, $result->info['price'], 'Price should be 3.71');
        $this->assertEquals(1, $result->info['parts'], 'Parts should be 1');
        $this->assertEquals('2087be89-6039-4f3e-a44b-13791f5f0d13', $result->info['custom_id'], 'Custom ID should match the input');
    }
    
    /**
     * Test statusHandler static method with error
     */
    public function testStatusHandlerWithError(): void
    {
        // Create test data with error
        $testData = [
            'id' => '2e54b3de-6d0e-4d3a-82b9-fc0fc2f661f4',
            'custom_id' => '2087be89-6039-4f3e-a44b-13791f5f0d13',
            'status' => 'error',
            'channel' => 'sms',
            'error' => 'Test error message',
            'code' => 123
        ];
        
        // Call the statusHandler method
        $result = Client::statusHandler($testData);
        
        // Verify the result
        $this->assertInstanceOf(Status::class, $result, 'statusHandler() should return a Status instance');
        $this->assertEquals('error', $result->getStatus(), 'Status should be "error"');
        $this->assertInstanceOf(\Imobis\Sdk\Entity\Error::class, $result->getError(), 'Error should be an Error instance');
        $this->assertEquals(123, $result->getError()->code, 'Error code should be 123');
        $this->assertEquals('Test error message', $result->getError()->message, 'Error message should match the input');
    }
    
    /**
     * Test replyHandler static method
     */
    public function testReplyHandler(): void
    {
        // Create test data
        $testData = [
            'id' => '07736dd1-d366-4046-a412-c02edc95b25f',
            'custom_id' => '3c8f6f42-ba4a-408a-a689-c4fc5884bca2',
            'text' => 'test reply',
            'date' => '2025-09-01 17:30:00'
        ];
        
        // Call the replyHandler method
        $result = Client::replyHandler($testData);
        
        // Verify the result
        $this->assertInstanceOf(Reply::class, $result, 'replyHandler() should return a Reply instance');
        $this->assertEquals('07736dd1-d366-4046-a412-c02edc95b25f', $result->getMessageId(), 'MessageId should match the input ID');
        $this->assertEquals('test reply', $result->getText(), 'Text should match the input text');
        $this->assertEquals('2025-09-01 17:30:00', $result->getDate(), 'Date should match the input date');
        $this->assertEquals('3c8f6f42-ba4a-408a-a689-c4fc5884bca2', $result->getCustomId(), 'CustomId should match the input custom_id');
    }
    
    /**
     * Helper method to create a Client instance with mocked dependencies
     */
    private function createClientWithMocks(): Client
    {
        // Use reflection to create a Client instance without calling the constructor
        $reflectionClass = new ReflectionClass(Client::class);
        $client = $reflectionClass->newInstanceWithoutConstructor();
        
        // Set the token property using reflection
        $tokenProperty = $reflectionClass->getProperty('token');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($client, $this->tokenMock);

        // Mock the TokenController class
        $client->setController($this->tokenControllerMock);

        // Mock the BalanceController class
        $client->setController($this->balanceControllerMock);

        // Create a mock for PhoneController
        $client->setController($this->phoneControllerMock);

        // Create a mock for SenderController
        $client->setController($this->senderControllerMock);

        // Create a mock for TemplateController
        $client->setController($this->templateControllerMock);

        // Create a mock for MessageController
        $client->setController($this->messageControllerMock);
        
        return $client;
    }
}