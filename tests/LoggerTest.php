<?php

namespace Nexus\Message\Sdk\Tests;

use Nexus\Message\Sdk\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

require_once 'vendor/autoload.php';

class LoggerTest extends TestCase
{
    /**
     * @var string
     */
    private $testLogFile;

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary log file for testing
        $this->testLogFile = sys_get_temp_dir() . '/logger_test_' . uniqid() . '.log';
        
        // Reset the singleton instance before each test
        $this->resetLoggerInstance();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the test log file
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        
        // Reset the singleton instance after each test
        $this->resetLoggerInstance();
    }

    /**
     * Reset the Logger singleton instance using reflection
     */
    private function resetLoggerInstance(): void
    {
        $reflection = new ReflectionClass(Logger::class);
        $instancesProperty = $reflection->getParentClass()->getProperty('instances');
        $instancesProperty->setAccessible(true);
        $instances = $instancesProperty->getValue();
        
        if (isset($instances[Logger::class])) {
            unset($instances[Logger::class]);
            $instancesProperty->setValue(null, $instances);
        }
    }

    /**
     * Test that Logger follows the singleton pattern
     */
    public function testLoggerIsSingleton(): void
    {
        $logger1 = Logger::getInstance($this->testLogFile);
        $logger2 = Logger::getInstance($this->testLogFile);
        
        $this->assertSame($logger1, $logger2, 'Logger should return the same instance');
    }

    /**
     * Test that Logger::log method works with different log levels
     * 
     * @dataProvider logLevelProvider
     */
    public function testLogWithDifferentLevels(string $level): void
    {
        // Initialize Logger with test log file
        Logger::getInstance($this->testLogFile);
        
        $message = "Test message for {$level}";
        $context = ['test_key' => 'test_value'];
        
        $logHash = Logger::log($level, $message, $context);
        
        $this->assertNotEmpty($logHash, 'Log hash should not be empty');
        $this->assertIsString($logHash, 'Log hash should be a string');
        
        // Check if the log file contains the message
        if (file_exists($this->testLogFile)) {
            $logContent = file_get_contents($this->testLogFile);
            $this->assertStringContainsString($message, $logContent, "Log file should contain the {$level} message");
            $this->assertStringContainsString($level, $logContent, "Log file should contain the {$level} level");
            $this->assertStringContainsString('test_value', $logContent, "Log file should contain the context values");
        }
    }

    /**
     * Test that Logger generates a log_hash if not provided
     */
    public function testLogHashGeneration(): void
    {
        // Initialize Logger with test log file
        Logger::getInstance($this->testLogFile);
        
        $message = 'Test message for hash generation';
        $logHash = Logger::log('info', $message);
        
        $this->assertNotEmpty($logHash, 'Log hash should be generated');
        
        // Test with provided log_hash
        $customHash = 'custom_hash_123';
        $logHashResult = Logger::log('info', $message, ['log_hash' => $customHash]);
        
        $this->assertEquals($customHash, $logHashResult, 'Logger should use the provided log_hash');
    }

    /**
     * Test that Logger handles invalid log levels gracefully
     */
    public function testInvalidLogLevel(): void
    {
        // Initialize Logger with test log file
        Logger::getInstance($this->testLogFile);
        
        $message = 'Test message with invalid level';
        $logHash = Logger::log('invalid_level', $message);
        
        $this->assertEmpty($logHash, 'Log hash should be empty for invalid log level');
    }

    /**
     * Data provider for log levels
     */
    public function logLevelProvider(): array
    {
        return [
            ['debug'],
            ['info'],
            ['notice'],
            ['warning'],
            ['error'],
            ['critical'],
            ['alert'],
            ['emergency'],
        ];
    }
}