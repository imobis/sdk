<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Sandbox;
use Imobis\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once 'vendor/autoload.php';

class SandboxTest extends TestCase
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
        $this->tokenMock->method('getProperties')->willReturn([
            'login' => 'test_login',
            'token' => $this->testApiKey,
            'category' => 'smsm',
            'active' => true
        ]);
        
        // Reset Sandbox static instance before each test
        $this->resetSandboxStatic();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset Sandbox static instance after each test
        $this->resetSandboxStatic();
    }

    /**
     * Reset the Sandbox static properties using reflection
     */
    private function resetSandboxStatic(): void
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
     * Test Singleton behavior - getInstance should always return the same instance
     */
    public function testGetInstance(): void
    {
        $sandbox1 = Sandbox::getInstance();
        $sandbox2 = Sandbox::getInstance();
        
        $this->assertSame($sandbox1, $sandbox2, 'getInstance should return the same instance');
        $this->assertInstanceOf(Sandbox::class, $sandbox1, 'getInstance should return a Sandbox instance');
    }

    /**
     * Test getInstance with Token parameter
     */
    public function testGetInstanceWithToken(): void
    {
        // Make sure we start with a fresh instance
        $this->resetSandboxStatic();
        
        $sandbox = Sandbox::getInstance($this->tokenMock);
        
        $this->assertInstanceOf(Sandbox::class, $sandbox, 'getInstance should return a Sandbox instance');
        $this->assertSame($this->tokenMock, $sandbox->token(), 'token() should return the provided Token instance');
    }

    /**
     * Test forgetInstance method
     */
    public function testForgetInstance(): void
    {
        $sandbox1 = Sandbox::getInstance();
        $result = $sandbox1->forgetInstance();
        $sandbox2 = Sandbox::getInstance();
        
        $this->assertTrue($result, 'forgetInstance should return true');
        $this->assertNotSame($sandbox1, $sandbox2, 'After forgetInstance, getInstance should return a new instance');
    }

    /**
     * Test activate and active methods
     */
    public function testActivate(): void
    {
        $sandbox = Sandbox::getInstance();
        
        // Default state should be inactive
        $this->assertFalse($sandbox->active(), 'Sandbox should be inactive by default');
        
        // Activate and check state
        $result = $sandbox->activate();
        
        $this->assertSame($sandbox, $result, 'activate should return $this for method chaining');
        $this->assertTrue($sandbox->active(), 'Sandbox should be active after activate() call');
    }

    /**
     * Test deactivate method
     */
    public function testDeactivate(): void
    {
        $sandbox = Sandbox::getInstance();
        
        // Activate first
        $sandbox->activate();
        $this->assertTrue($sandbox->active(), 'Sandbox should be active');
        
        // Deactivate and check state
        $sandbox->deactivate();
        $this->assertFalse($sandbox->active(), 'Sandbox should be inactive after deactivate() call');
    }

    /**
     * Test token method
     */
    public function testToken(): void
    {
        // Make sure we start with a fresh instance
        $this->resetSandboxStatic();
        
        // Create Sandbox with token
        $sandbox = Sandbox::getInstance($this->tokenMock);
        
        $this->assertSame($this->tokenMock, $sandbox->token(), 'token() should return the Token instance');
        
        // Reset and create without token
        $this->resetSandboxStatic();
        $sandbox = Sandbox::getInstance();
        
        $this->assertNull($sandbox->token(), 'token() should return null when no token was provided');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $sandbox = Sandbox::getInstance();
        
        // Initial state
        $this->assertEmpty($sandbox->getChanges(), 'Changes should be empty initially');
        
        // Activate and touch
        $sandbox->activate()->touch();
        
        $changes = $sandbox->getChanges();
        $this->assertArrayHasKey('active', $changes, 'Changes should contain active key after touch');
        $this->assertTrue($changes['active'], 'active value in changes should be true');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $sandbox = Sandbox::getInstance();
        
        // Initial state
        $this->assertEmpty($sandbox->getOriginal(), 'Original should be empty initially');
        
        // Call getProperties to populate original
        $sandbox->getProperties();
        
        $original = $sandbox->getOriginal();
        $this->assertArrayHasKey('active', $original, 'Original should contain active key');
        $this->assertArrayHasKey('token', $original, 'Original should contain token key');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        // Make sure we start with a fresh instance
        $this->resetSandboxStatic();
        
        $sandbox = Sandbox::getInstance();
        
        // Get initial changes
        $initialChanges = $sandbox->getChanges();
        
        // Activate and touch
        $sandbox->activate()->touch();
        
        $changes = $sandbox->getChanges();
        $this->assertArrayHasKey('active', $changes, 'Changes should contain active key after touch');
        $this->assertTrue($changes['active'], 'active value in changes should be true');
        
        // Verify changes were updated
        $this->assertNotEquals($initialChanges, $changes, 'Changes should be updated after touch');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        // Make sure we start with a fresh instance
        $this->resetSandboxStatic();
        
        // Test with token
        $sandbox = Sandbox::getInstance($this->tokenMock);
        
        // Get the initial active state
        $initialActiveState = $sandbox->active();
        
        $properties = $sandbox->getProperties();
        
        $this->assertArrayHasKey('active', $properties, 'Properties should contain active key');
        $this->assertArrayHasKey('token', $properties, 'Properties should contain token key');
        $this->assertEquals($initialActiveState, $properties['active'], 'active property should match the current state');
        $this->assertEquals($this->testApiKey, $properties['token'], 'token should match the test API key');
        
        // Test without token
        $this->resetSandboxStatic();
        $sandbox = Sandbox::getInstance();
        
        // Get the initial active state
        $initialActiveState = $sandbox->active();
        
        $properties = $sandbox->getProperties();
        
        $this->assertArrayHasKey('active', $properties, 'Properties should contain active key');
        $this->assertArrayHasKey('token', $properties, 'Properties should contain token key');
        $this->assertEquals($initialActiveState, $properties['active'], 'active property should match the current state');
        $this->assertNull($properties['token'], 'token should be null');
    }
}