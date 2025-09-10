<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Entity\Balance;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once 'vendor/autoload.php';

class BalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset Balance singleton instance before each test
        $this->resetBalanceInstance();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        
        // Reset Balance singleton instance after each test
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
     * Test Singleton behavior - getInstance should always return the same instance
     */
    public function testGetInstance(): void
    {
        $balance1 = Balance::getInstance();
        $balance2 = Balance::getInstance();
        
        $this->assertSame($balance1, $balance2, 'getInstance should return the same instance');
        $this->assertInstanceOf(Balance::class, $balance1, 'getInstance should return a Balance instance');
    }

    /**
     * Test forgetInstance method
     */
    public function testForgetInstance(): void
    {
        $balance1 = Balance::getInstance();
        $result = $balance1->forgetInstance();
        $balance2 = Balance::getInstance();
        
        $this->assertTrue($result, 'forgetInstance should return true');
        $this->assertNotSame($balance1, $balance2, 'After forgetInstance, getInstance should return a new instance');
    }

    /**
     * Test getBalance method
     */
    public function testGetBalance(): void
    {
        $balance = Balance::getInstance();
        
        // Default balance should be 0.0
        $this->assertEquals(0.0, $balance->getBalance(), 'Default balance should be 0.0');
        
        // Set balance using magic __set method
        $balance->balance = 100.5;
        
        $this->assertEquals(100.5, $balance->getBalance(), 'getBalance() should return the updated balance value');
    }

    /**
     * Test getCurrency method
     */
    public function testGetCurrency(): void
    {
        $balance = Balance::getInstance();
        
        // Default currency should be 'RUB'
        $this->assertEquals('RUB', $balance->getCurrency(), 'Default currency should be RUB');
        
        // Set currency using magic __set method
        $balance->currency = 'USD';
        
        $this->assertEquals('USD', $balance->getCurrency(), 'getCurrency() should return the updated currency value');
    }

    /**
     * Test isFresh method
     */
    public function testIsFresh(): void
    {
        $balance = Balance::getInstance();
        
        // Default fresh state should be false
        $this->assertFalse($balance->isFresh(), 'Balance should not be fresh by default');
        
        // Call touch to set fresh to true
        $balance->touch();
        
        $this->assertTrue($balance->isFresh(), 'Balance should be fresh after touch() call');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $balance = Balance::getInstance();
        
        // Initial state
        $this->assertEmpty($balance->getChanges(), 'Changes should be empty initially');
        
        // Call touch
        $balance->touch();
        
        $changes = $balance->getChanges();
        $this->assertArrayHasKey('fresh', $changes, 'Changes should contain fresh key after touch');
        $this->assertTrue($changes['fresh'], 'fresh value in changes should be true');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $balance = Balance::getInstance();
        
        // Initial state
        $this->assertEmpty($balance->getOriginal(), 'Original should be empty initially');
        
        // Call getProperties to populate original
        $balance->getProperties();
        
        $original = $balance->getOriginal();
        $this->assertArrayHasKey('balance', $original, 'Original should contain balance key');
        $this->assertArrayHasKey('fresh', $original, 'Original should contain fresh key');
        $this->assertArrayHasKey('currency', $original, 'Original should contain currency key');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        // Make sure we start with a fresh instance
        $this->resetBalanceInstance();
        
        $balance = Balance::getInstance();
        
        // Get initial changes
        $initialChanges = $balance->getChanges();
        
        // Update balance and touch
        $balance->balance = 200.0;
        $balance->touch();
        
        $changes = $balance->getChanges();
        $this->assertArrayHasKey('balance', $changes, 'Changes should contain balance key after update');
        $this->assertArrayHasKey('fresh', $changes, 'Changes should contain fresh key after touch');
        $this->assertEquals(200.0, $changes['balance'], 'balance value in changes should be 200.0');
        $this->assertTrue($changes['fresh'], 'fresh value in changes should be true');
        
        // Verify changes were updated
        $this->assertNotEquals($initialChanges, $changes, 'Changes should be updated after balance update and touch');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        // Make sure we start with a fresh instance
        $this->resetBalanceInstance();
        
        $balance = Balance::getInstance();
        
        // Set some values
        $balance->balance = 300.0;
        $balance->currency = 'EUR';
        $balance->touch();
        
        $properties = $balance->getProperties();
        
        $this->assertArrayHasKey('balance', $properties, 'Properties should contain balance key');
        $this->assertArrayHasKey('fresh', $properties, 'Properties should contain fresh key');
        $this->assertArrayHasKey('currency', $properties, 'Properties should contain currency key');
        $this->assertEquals(300.0, $properties['balance'], 'balance property should match the current value');
        $this->assertEquals('EUR', $properties['currency'], 'currency property should match the current value');
        $this->assertTrue($properties['fresh'], 'fresh property should be true after touch');
    }

    /**
     * Test magic __set method with balance property
     */
    public function testMagicSetWithBalance(): void
    {
        $balance = Balance::getInstance();
        
        // Initial state
        $this->assertEquals(0.0, $balance->getBalance(), 'Initial balance should be 0.0');
        $this->assertEmpty($balance->getChanges(), 'Changes should be empty initially');
        
        // Set balance to a new value
        $balance->balance = 150.75;
        
        // Verify balance was updated
        $this->assertEquals(150.75, $balance->getBalance(), 'Balance should be updated to 150.75');
        
        // Verify changes were recorded
        $changes = $balance->getChanges();
        $this->assertArrayHasKey('balance', $changes, 'Changes should contain balance key');
        $this->assertEquals(150.75, $changes['balance'], 'balance value in changes should be 150.75');
        
        // Set balance to the same value (should not update changes)
        $initialChanges = $balance->getChanges();
        $balance->balance = 150.75;
        $this->assertEquals($initialChanges, $balance->getChanges(), 'Changes should not be updated when setting the same value');
        
        // Set balance to empty value (should not update)
        $balance->balance = '';
        $this->assertEquals(150.75, $balance->getBalance(), 'Balance should not be updated with empty value');
    }

    /**
     * Test magic __set method with currency property
     */
    public function testMagicSetWithCurrency(): void
    {
        $balance = Balance::getInstance();
        
        // Initial state
        $this->assertEquals('RUB', $balance->getCurrency(), 'Initial currency should be RUB');
        $this->assertEmpty($balance->getChanges(), 'Changes should be empty initially');
        
        // Set currency to a new value
        $balance->currency = 'USD';
        
        // Verify currency was updated
        $this->assertEquals('USD', $balance->getCurrency(), 'Currency should be updated to USD');
        
        // Verify changes were recorded
        $changes = $balance->getChanges();
        $this->assertArrayHasKey('currency', $changes, 'Changes should contain currency key');
        $this->assertEquals('USD', $changes['currency'], 'currency value in changes should be USD');
        
        // Set currency to the same value (should not update changes)
        $initialChanges = $balance->getChanges();
        $balance->currency = 'USD';
        $this->assertEquals($initialChanges, $balance->getChanges(), 'Changes should not be updated when setting the same value');
        
        // Set currency to empty value (should not update)
        $balance->currency = '';
        $this->assertEquals('USD', $balance->getCurrency(), 'Currency should not be updated with empty value');
        
        // Set currency to non-string value (should not update)
        $balance->currency = 123;
        $this->assertEquals('USD', $balance->getCurrency(), 'Currency should not be updated with non-string value');
    }

    /**
     * Test magic __set method with invalid property
     */
    public function testMagicSetWithInvalidProperty(): void
    {
        $balance = Balance::getInstance();
        
        // Set an invalid property
        $balance->invalidProperty = 'test';
        
        // Verify changes were not recorded
        $changes = $balance->getChanges();
        $this->assertArrayNotHasKey('invalidProperty', $changes, 'Changes should not contain invalid property key');
    }
}