<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Entity\Phone;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class PhoneTest extends TestCase
{
    /**
     * @var array
     */
    private $testData;

    /**
     * @var string
     */
    private $testPhoneNumber;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testPhoneNumber = '+7 (999) 123-45-67';
        $this->testData = [
            'number' => $this->testPhoneNumber,
            'info' => [
                'phone_number' => '79991234567',
                'status' => 'ok',
                'country' => 'Russia',
                'operator' => 'MTS',
                'region_id' => '77',
                'region_name' => 'Moscow',
                'region_timezone' => 'Europe/Moscow'
            ]
        ];
    }

    /**
     * Test constructor with valid data
     */
    public function testConstructorWithValidData(): void
    {
        $phone = new Phone($this->testData);
        
        $this->assertInstanceOf(Phone::class, $phone, 'Constructor should return a Phone instance');
        $this->assertEquals('79991234567', $phone->getNumber(), 'getNumber() should return the phone number from info');
        $this->assertEquals('ok', $phone->getStatus(), 'getStatus() should return the status from info');
        $this->assertEquals('Russia', $phone->getCountry(), 'getCountry() should return the country from info');
        $this->assertEquals('MTS', $phone->getOperator(), 'getOperator() should return the operator from info');
        $this->assertEquals('77', $phone->getRegionId(), 'getRegionId() should return the region ID from info');
        $this->assertEquals('Moscow', $phone->getRegionName(), 'getRegionName() should return the region name from info');
        $this->assertEquals('Europe/Moscow', $phone->getTimezone(), 'getTimezone() should return the timezone from info');
    }

    /**
     * Test constructor with partial data
     */
    public function testConstructorWithPartialData(): void
    {
        $partialData = [
            'number' => $this->testPhoneNumber
        ];
        
        $phone = new Phone($partialData);
        
        $this->assertInstanceOf(Phone::class, $phone, 'Constructor should return a Phone instance');
        $this->assertEquals('79991234567', $phone->getNumber(), 'getNumber() should return the parsed phone number');
        $this->assertEquals('', $phone->getStatus(), 'getStatus() should return empty string when info is not provided');
        $this->assertEquals('', $phone->getCountry(), 'getCountry() should return empty string when info is not provided');
    }

    /**
     * Test constructor with empty data
     */
    public function testConstructorWithEmptyData(): void
    {
        $phone = new Phone();
        
        $this->assertInstanceOf(Phone::class, $phone, 'Constructor should return a Phone instance');
        $this->assertEquals('', $phone->getNumber(), 'getNumber() should return empty string when no data is provided');
        $this->assertEquals('', $phone->getStatus(), 'getStatus() should return empty string when no data is provided');
    }

    /**
     * Test withoutCheck method
     */
    public function testWithoutCheck(): void
    {
        $phone = new Phone($this->testData);
        
        // Default check should be true
        $this->assertTrue($phone->needCheck(), 'needCheck() should return true by default');
        
        // Call withoutCheck
        $result = $phone->withoutCheck();
        
        // Check should now be false
        $this->assertFalse($phone->needCheck(), 'needCheck() should return false after withoutCheck() call');
        
        // Method should return $this for chaining
        $this->assertSame($phone, $result, 'withoutCheck() should return $this for method chaining');
    }

    /**
     * Test valid method with check enabled and valid status
     */
    public function testValidWithCheckEnabledAndValidStatus(): void
    {
        $phone = new Phone($this->testData);
        
        $this->assertTrue($phone->valid(), 'valid() should return true when status is "ok"');
    }

    /**
     * Test valid method with check enabled and invalid status
     */
    public function testValidWithCheckEnabledAndInvalidStatus(): void
    {
        $data = $this->testData;
        $data['info']['status'] = 'invalid';
        
        $phone = new Phone($data);
        
        $this->assertFalse($phone->valid(), 'valid() should return false when status is not "ok"');
    }

    /**
     * Test valid method with check disabled
     */
    public function testValidWithCheckDisabled(): void
    {
        $phone = new Phone($this->testData);
        $phone->withoutCheck();
        
        // Should be valid regardless of status when check is disabled
        $this->assertTrue($phone->valid(), 'valid() should return true when check is disabled, regardless of status');
    }

    /**
     * Test setNumber and getNumber methods
     */
    public function testSetNumberAndGetNumber(): void
    {
        $phone = new Phone();
        
        // Initial number should be empty
        $this->assertEquals('', $phone->getNumber(), 'Initial number should be empty');
        
        // Set a new number
        $result = $phone->setNumber('+7 (888) 765-43-21');
        
        // Number should be parsed and set
        $this->assertEquals('78887654321', $phone->getNumber(), 'getNumber() should return the parsed phone number');
        
        // Method should return $this for chaining
        $this->assertSame($phone, $result, 'setNumber() should return $this for method chaining');
    }

    /**
     * Test setInfo and related getter methods
     */
    public function testSetInfoAndGetters(): void
    {
        $phone = new Phone();
        
        // Initial info should be empty
        $this->assertEquals('', $phone->getStatus(), 'Initial status should be empty');
        $this->assertEquals('', $phone->getCountry(), 'Initial country should be empty');
        
        // Set new info
        $info = [
            'phone_number' => '79991234567',
            'status' => 'ok',
            'country' => 'Russia',
            'operator' => 'MTS',
            'region_id' => '77',
            'region_name' => 'Moscow',
            'region_timezone' => 'Europe/Moscow'
        ];
        
        $result = $phone->setInfo($info);
        
        // Info should be set and accessible via getters
        $this->assertEquals('79991234567', $phone->getNumber(), 'getNumber() should return the phone number from info');
        $this->assertEquals('ok', $phone->getStatus(), 'getStatus() should return the status from info');
        $this->assertEquals('Russia', $phone->getCountry(), 'getCountry() should return the country from info');
        $this->assertEquals('MTS', $phone->getOperator(), 'getOperator() should return the operator from info');
        $this->assertEquals('77', $phone->getRegionId(), 'getRegionId() should return the region ID from info');
        $this->assertEquals('Moscow', $phone->getRegionName(), 'getRegionName() should return the region name from info');
        $this->assertEquals('Europe/Moscow', $phone->getTimezone(), 'getTimezone() should return the timezone from info');
        
        // Method should return $this for chaining
        $this->assertSame($phone, $result, 'setInfo() should return $this for method chaining');
    }

    /**
     * Test needCheck and checked methods
     */
    public function testNeedCheckAndChecked(): void
    {
        $phone = new Phone();
        
        // Default check should be true
        $this->assertTrue($phone->needCheck(), 'needCheck() should return true by default');
        
        // Default initialize should be false
        $this->assertFalse($phone->checked(), 'checked() should return false by default');
        
        // Set info and touch to set initialize to true
        $phone->setInfo(['status' => 'ok'])->touch();
        
        // initialize should now be true
        $this->assertTrue($phone->checked(), 'checked() should return true after setInfo() and touch() calls');
    }

    /**
     * Test parseNumber method with various phone formats
     */
    public function testParseNumber(): void
    {
        // Use reflection to access the protected parseNumber method
        $reflection = new ReflectionMethod(Phone::class, 'parseNumber');
        $reflection->setAccessible(true);
        
        // Test with various phone formats
        $this->assertEquals('79991234567', $reflection->invoke(null, '+7 (999) 123-45-67'), 'parseNumber() should extract only digits from phone number');
        $this->assertEquals('79991234567', $reflection->invoke(null, '7 999 123 45 67'), 'parseNumber() should extract only digits from phone number');
        $this->assertEquals('79991234567', $reflection->invoke(null, '7(999)123-45-67'), 'parseNumber() should extract only digits from phone number');
        $this->assertEquals('79991234567', $reflection->invoke(null, '79991234567'), 'parseNumber() should return the same number if it contains only digits');
        $this->assertEquals('', $reflection->invoke(null, 'not-a-number'), 'parseNumber() should return empty string for non-numeric input');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $phone = new Phone();
        
        // Initial changes should be empty
        $this->assertEmpty($phone->getChanges(), 'Initial changes should be empty');
        
        // Set info to add to changes
        $phone->setInfo(['status' => 'ok']);
        
        // Changes should contain info
        $changes = $phone->getChanges();
        $this->assertArrayHasKey('info', $changes, 'Changes should contain info key after setInfo() call');
        
        // Call touch
        $phone->touch();
        
        // Changes should now contain initialize
        $changes = $phone->getChanges();
        $this->assertArrayHasKey('initialize', $changes, 'Changes should contain initialize key after touch() call');
        $this->assertTrue($changes['initialize'], 'initialize value in changes should be true');
        
        // checked() should now return true
        $this->assertTrue($phone->checked(), 'checked() should return true after touch() call');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $phone = new Phone($this->testData);
        
        // Initial original should be empty
        $this->assertEmpty($phone->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $phone->getProperties();
        
        // Original should now contain data
        $original = $phone->getOriginal();
        $this->assertArrayHasKey('check', $original, 'Original should contain check key');
        $this->assertArrayHasKey('number', $original, 'Original should contain number key');
        $this->assertArrayHasKey('info', $original, 'Original should contain info key');
        $this->assertEquals('79991234567', $original['number'], 'Original number should match the phone number');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        $phone = new Phone();
        
        // Initial changes should be empty
        $this->assertEmpty($phone->getChanges(), 'Initial changes should be empty');
        
        // Set info to add to changes
        $phone->setInfo(['status' => 'ok']);
        
        // Changes should contain info
        $changes = $phone->getChanges();
        $this->assertArrayHasKey('info', $changes, 'Changes should contain info key after setInfo() call');
        $this->assertEquals(['status' => 'ok'], $changes['info'], 'info value in changes should match the provided info');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $phone = new Phone($this->testData);
        
        $properties = $phone->getProperties();
        
        $this->assertArrayHasKey('check', $properties, 'Properties should contain check key');
        $this->assertArrayHasKey('number', $properties, 'Properties should contain number key');
        $this->assertArrayHasKey('info', $properties, 'Properties should contain info key');
        $this->assertEquals('79991234567', $properties['number'], 'Properties number should match the phone number');
        $this->assertEquals('ok', $properties['info']['status'], 'Properties info status should match the status');
        $this->assertEquals('Russia', $properties['info']['country'], 'Properties info country should match the country');
    }
}