<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Token;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

class TokenTest extends TestCase
{
    /**
     * @var string
     */
    private $testToken;

    /**
     * @var string
     */
    private $testCategory;

    /**
     * @var string
     */
    private $testLogin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testToken = Config::TEST_API_KEY; // Valid UUID format
        $this->testCategory = 'smsm';
        $this->testLogin = 'test_login';
    }

    /**
     * Test constructor with valid token and default category
     */
    public function testConstructorWithValidTokenAndDefaultCategory(): void
    {
        $token = new Token($this->testToken);
        
        $this->assertInstanceOf(Token::class, $token, 'Constructor should return a Token instance');
        $this->assertEquals($this->testToken, $token->getToken(), 'getToken() should return the token provided to constructor');
        $this->assertEquals('smsm', $token->getCategory(), 'getCategory() should return the default category');
        $this->assertEquals('', $token->getLogin(), 'getLogin() should return empty string by default');
        $this->assertFalse($token->getActive(), 'getActive() should return false by default');
    }

    /**
     * Test constructor with valid token and custom category
     */
    public function testConstructorWithValidTokenAndCustomCategory(): void
    {
        $customCategory = 'custom';
        $token = new Token($this->testToken, $customCategory);
        
        $this->assertInstanceOf(Token::class, $token, 'Constructor should return a Token instance');
        $this->assertEquals($this->testToken, $token->getToken(), 'getToken() should return the token provided to constructor');
        $this->assertEquals($customCategory, $token->getCategory(), 'getCategory() should return the custom category');
    }

    /**
     * Test validate method with valid token
     */
    public function testValidateWithValidToken(): void
    {
        $token = new Token($this->testToken);
        
        $result = $token->validate();
        
        $this->assertTrue($result, 'validate() should return true for a valid token');
    }

    /**
     * Test validate method with invalid token
     */
    public function testValidateWithInvalidToken(): void
    {
        $invalidToken = 'invalid-token';
        $token = new Token($invalidToken);
        
        $result = $token->validate();
        
        $this->assertFalse($result, 'validate() should return false for an invalid token');
    }

    /**
     * Test getToken method
     */
    public function testGetToken(): void
    {
        $token = new Token($this->testToken);
        
        $result = $token->getToken();
        
        $this->assertEquals($this->testToken, $result, 'getToken() should return the token provided to constructor');
    }

    /**
     * Test getLogin method
     */
    public function testGetLogin(): void
    {
        $token = new Token($this->testToken);
        
        // Initial login should be empty
        $this->assertEquals('', $token->getLogin(), 'Initial login should be empty');
        
        // Set login using magic __set method
        $token->login = $this->testLogin;
        
        $this->assertEquals($this->testLogin, $token->getLogin(), 'getLogin() should return the login set via magic __set');
    }

    /**
     * Test getActive method
     */
    public function testGetActive(): void
    {
        $token = new Token($this->testToken);
        
        // Initial active should be false
        $this->assertFalse($token->getActive(), 'Initial active should be false');
        
        // Set login and call touch to set active to true
        $token->login = $this->testLogin;
        $token->touch();
        
        $this->assertTrue($token->getActive(), 'getActive() should return true after setting login and calling touch');
    }

    /**
     * Test getCategory method
     */
    public function testGetCategory(): void
    {
        $token = new Token($this->testToken);
        
        $result = $token->getCategory();
        
        $this->assertEquals($this->testCategory, $result, 'getCategory() should return the category provided to constructor');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $token = new Token($this->testToken);
        
        // Initial changes should be empty
        $this->assertEmpty($token->getChanges(), 'Initial changes should be empty');
        
        // Call touch without setting login (should not change active)
        $token->touch();
        
        // Changes should still be empty
        $this->assertEmpty($token->getChanges(), 'Changes should be empty when touch is called without setting login');
        $this->assertFalse($token->getActive(), 'active should still be false');
        
        // Set login and call touch
        $token->login = $this->testLogin;
        $token->touch();
        
        // Changes should now contain active
        $changes = $token->getChanges();
        $this->assertArrayHasKey('active', $changes, 'Changes should contain active key after setting login and calling touch');
        $this->assertTrue($changes['active'], 'active value in changes should be true');
        $this->assertTrue($token->getActive(), 'getActive() should return true after setting login and calling touch');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $token = new Token($this->testToken);
        
        // Initial original should be empty
        $this->assertEmpty($token->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $token->getProperties();
        
        // Original should now contain data
        $original = $token->getOriginal();
        $this->assertArrayHasKey('login', $original, 'Original should contain login key');
        $this->assertArrayHasKey('token', $original, 'Original should contain token key');
        $this->assertArrayHasKey('category', $original, 'Original should contain category key');
        $this->assertArrayHasKey('active', $original, 'Original should contain active key');
        $this->assertEquals($this->testToken, $original['token'], 'Original token should match the token provided to constructor');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        $token = new Token($this->testToken);
        
        // Initial changes should be empty
        $this->assertEmpty($token->getChanges(), 'Initial changes should be empty');
        
        // Set login to add to changes
        $token->login = $this->testLogin;
        
        // Changes should contain login
        $changes = $token->getChanges();
        $this->assertArrayHasKey('login', $changes, 'Changes should contain login key after setting login');
        $this->assertEquals($this->testLogin, $changes['login'], 'login value in changes should match the provided login');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $token = new Token($this->testToken);
        
        // Set login
        $token->login = $this->testLogin;
        $token->touch();
        
        $properties = $token->getProperties();
        
        $this->assertArrayHasKey('login', $properties, 'Properties should contain login key');
        $this->assertArrayHasKey('token', $properties, 'Properties should contain token key');
        $this->assertArrayHasKey('category', $properties, 'Properties should contain category key');
        $this->assertArrayHasKey('active', $properties, 'Properties should contain active key');
        $this->assertEquals($this->testLogin, $properties['login'], 'Properties login should match the login');
        $this->assertEquals($this->testToken, $properties['token'], 'Properties token should match the token');
        $this->assertEquals($this->testCategory, $properties['category'], 'Properties category should match the category');
        $this->assertTrue($properties['active'], 'Properties active should be true after setting login and calling touch');
    }

    /**
     * Test magic __set method with login property
     */
    public function testMagicSetWithLogin(): void
    {
        $token = new Token($this->testToken);
        
        // Initial login should be empty
        $this->assertEquals('', $token->getLogin(), 'Initial login should be empty');
        $this->assertEmpty($token->getChanges(), 'Changes should be empty initially');
        
        // Set login to a new value
        $token->login = $this->testLogin;
        
        // Verify login was updated
        $this->assertEquals($this->testLogin, $token->getLogin(), 'Login should be updated to the new value');
        
        // Verify changes were recorded
        $changes = $token->getChanges();
        $this->assertArrayHasKey('login', $changes, 'Changes should contain login key');
        $this->assertEquals($this->testLogin, $changes['login'], 'login value in changes should match the provided login');
    }

    /**
     * Test magic __set method with empty login
     */
    public function testMagicSetWithEmptyLogin(): void
    {
        $token = new Token($this->testToken);
        
        // Set login to a value first
        $token->login = $this->testLogin;
        
        // Verify login was updated
        $this->assertEquals($this->testLogin, $token->getLogin(), 'Login should be updated to the new value');
        
        // Set login to empty value
        $token->login = '';
        
        // Verify login was not updated
        $this->assertEquals($this->testLogin, $token->getLogin(), 'Login should not be updated with empty value');
    }

    /**
     * Test magic __set method with non-string login
     */
    public function testMagicSetWithNonStringLogin(): void
    {
        $token = new Token($this->testToken);
        
        // Set login to a non-string value
        $token->login = 123;
        
        // Verify login was not updated
        $this->assertEquals('', $token->getLogin(), 'Login should not be updated with non-string value');
    }

    /**
     * Test magic __set method with invalid token
     */
    public function testMagicSetWithInvalidToken(): void
    {
        $invalidToken = 'invalid-token';
        $token = new Token($invalidToken);
        
        // Set login
        $token->login = $this->testLogin;
        
        // Verify login was not updated because token is invalid
        $this->assertEquals('', $token->getLogin(), 'Login should not be updated when token is invalid');
    }

    /**
     * Test magic __set method with invalid property
     */
    public function testMagicSetWithInvalidProperty(): void
    {
        $token = new Token($this->testToken);
        
        // Set an invalid property
        $token->invalidProperty = 'test';
        
        // Verify changes were not recorded
        $changes = $token->getChanges();
        $this->assertArrayNotHasKey('invalidProperty', $changes, 'Changes should not contain invalid property key');
    }
}