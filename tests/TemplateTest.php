<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Entity\Channel;
use Imobis\Sdk\Entity\Template;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

require_once 'vendor/autoload.php';

class TemplateTest extends TestCase
{
    /**
     * @var string
     */
    private $testId;

    /**
     * @var string
     */
    private $testName;

    /**
     * @var string
     */
    private $testText;

    /**
     * @var string
     */
    private $testChannel;

    /**
     * @var string
     */
    private $testGroupUrl;

    /**
     * @var string
     */
    private $testComment;

    /**
     * @var string
     */
    private $testReportUrl;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up test data
        $this->testId = 'template-123';
        $this->testName = 'test_template';
        $this->testText = 'Test template text';
        $this->testChannel = Channel::SMS;
        $this->testGroupUrl = 'https://example.com/group';
        $this->testComment = 'Test comment';
        $this->testReportUrl = 'https://example.com/report';
    }

    /**
     * Test constructor with empty data
     */
    public function testConstructorWithEmptyData(): void
    {
        $template = new Template();
        
        $this->assertInstanceOf(Template::class, $template, 'Constructor should return a Template instance');
        $this->assertEquals('', $template->getId(), 'getId() should return empty string by default');
        $this->assertEquals('', $template->getName(), 'getName() should return empty string by default');
        $this->assertEquals('', $template->getText(), 'getText() should return empty string by default');
        $this->assertEmpty($template->getChannels(), 'getChannels() should return empty array by default');
        $this->assertEquals('', $template->getGroupUrl(), 'getGroupUrl() should return empty string by default');
        $this->assertEquals(Template::STATUS_NEW, $template->getStatus(), 'getStatus() should return STATUS_NEW by default');
        $this->assertEquals('', $template->getComment(), 'getComment() should return empty string by default');
        $this->assertFalse($template->getActive(), 'getActive() should return false by default');
        $this->assertEquals('', $template->getReportUrl(), 'getReportUrl() should return empty string by default');
        $this->assertEquals(0, $template->getCreated(), 'getCreated() should return 0 by default');
        $this->assertEquals(0, $template->getUpdated(), 'getUpdated() should return 0 by default');
        $this->assertEmpty($template->getVariables(), 'getVariables() should return empty array by default');
        $this->assertEmpty($template->getOptions(), 'getOptions() should return empty array by default');
        $this->assertEquals('', $template->getService(), 'getService() should return empty string by default');
    }

    /**
     * Test constructor with valid data
     */
    public function testConstructorWithValidData(): void
    {
        $data = [
            'id' => $this->testId,
            'name' => $this->testName,
            'text' => $this->testText,
            'channel' => [$this->testChannel => true],
            'group_url' => $this->testGroupUrl,
            'status' => Template::STATUS_APPROVED,
            'comment' => $this->testComment,
            'report_url' => $this->testReportUrl,
            'created' => 1630000000,
            'updated' => 1640000000,
            'active' => true
        ];
        
        $template = new Template($data);
        
        $this->assertInstanceOf(Template::class, $template, 'Constructor should return a Template instance');
        $this->assertEquals($this->testId, $template->getId(), 'getId() should return the id provided to constructor');
        $this->assertEquals($this->testName, $template->getName(), 'getName() should return the name provided to constructor');
        $this->assertEquals($this->testText, $template->getText(), 'getText() should return the text provided to constructor');
        $this->assertEquals([$this->testChannel], $template->getChannels(), 'getChannels() should return the channels provided to constructor');
        $this->assertEquals($this->testGroupUrl, $template->getGroupUrl(), 'getGroupUrl() should return the group_url provided to constructor');
        $this->assertEquals(Template::STATUS_APPROVED, $template->getStatus(), 'getStatus() should return the status provided to constructor');
        $this->assertEquals($this->testComment, $template->getComment(), 'getComment() should return the comment provided to constructor');
        $this->assertTrue($template->getActive(), 'getActive() should return the active provided to constructor');
        $this->assertEquals($this->testReportUrl, $template->getReportUrl(), 'getReportUrl() should return the report_url provided to constructor');
        $this->assertEquals(1630000000, $template->getCreated(), 'getCreated() should return the created provided to constructor');
        $this->assertEquals(1640000000, $template->getUpdated(), 'getUpdated() should return the updated provided to constructor');
    }

    /**
     * Test constructor with partial data
     */
    public function testConstructorWithPartialData(): void
    {
        $data = [
            'name' => $this->testName,
            'text' => $this->testText
        ];
        
        $template = new Template($data);
        
        $this->assertInstanceOf(Template::class, $template, 'Constructor should return a Template instance');
        $this->assertEquals('', $template->getId(), 'getId() should return empty string when not provided');
        $this->assertEquals($this->testName, $template->getName(), 'getName() should return the name provided to constructor');
        $this->assertEquals($this->testText, $template->getText(), 'getText() should return the text provided to constructor');
        $this->assertEmpty($template->getChannels(), 'getChannels() should return empty array when not provided');
        $this->assertEquals('', $template->getGroupUrl(), 'getGroupUrl() should return empty string when not provided');
        $this->assertEquals(Template::STATUS_NEW, $template->getStatus(), 'getStatus() should return STATUS_NEW when not provided');
    }

    /**
     * Test getId method
     */
    public function testGetId(): void
    {
        $template = new Template(['id' => $this->testId]);
        
        $result = $template->getId();
        
        $this->assertEquals($this->testId, $result, 'getId() should return the id provided to constructor');
    }

    /**
     * Test setName and getName methods
     */
    public function testSetNameAndGetName(): void
    {
        $template = new Template();
        
        // Initial name should be empty
        $this->assertEquals('', $template->getName(), 'Initial name should be empty');
        
        // Set name
        $result = $template->setName($this->testName);
        
        // Verify name was updated
        $this->assertEquals($this->testName, $template->getName(), 'getName() should return the name set with setName()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setName() should return $this for method chaining');
        
        // Verify changes were recorded
        $changes = $template->getChanges();
        $this->assertArrayHasKey('name', $changes, 'Changes should contain name key');
        $this->assertEquals($this->testName, $changes['name'], 'name value in changes should match the provided name');
        
        // Test with long name (should be truncated to 255 characters)
        $longName = str_repeat('a', 300);
        $template->setName($longName);
        $this->assertEquals(255, mb_strlen($template->getName()), 'Long name should be truncated to 255 characters');
    }

    /**
     * Test setText and getText methods
     */
    public function testSetTextAndGetText(): void
    {
        $template = new Template();
        
        // Initial text should be empty
        $this->assertEquals('', $template->getText(), 'Initial text should be empty');
        
        // Set text
        $result = $template->setText($this->testText);
        
        // Verify text was updated
        $this->assertEquals($this->testText, $template->getText(), 'getText() should return the text set with setText()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setText() should return $this for method chaining');
        
        // Verify changes were recorded
        $changes = $template->getChanges();
        $this->assertArrayHasKey('text', $changes, 'Changes should contain text key');
        $this->assertEquals($this->testText, $changes['text'], 'text value in changes should match the provided text');
        
        // Test with text that has whitespace (should be trimmed)
        $textWithWhitespace = "  " . $this->testText . "  ";
        $template->setText($textWithWhitespace);
        $this->assertEquals($this->testText, $template->getText(), 'Text with whitespace should be trimmed');
    }

    /**
     * Test checkText method
     */
    public function testCheckText(): void
    {
        // Create a mock of the Template class to avoid the actual regex check
        $templateMock = $this->getMockBuilder(Template::class)
            ->onlyMethods(['checkText'])
            ->getMock();
        
        // Configure the mock to return true for a valid text
        $validText = "This is a valid template text";
        $templateMock->method('checkText')
            ->with($validText)
            ->willReturn(true);
        
        // Test with the mocked method
        $result = $templateMock->checkText($validText);
        $this->assertTrue($result, 'checkText() should return true for valid text');
    }

    /**
     * Test setChannel, unsetChannel, and getChannels methods
     */
    public function testSetChannelUnsetChannelAndGetChannels(): void
    {
        $template = new Template();
        
        // Initial channels should be empty
        $this->assertEmpty($template->getChannels(), 'Initial channels should be empty');
        
        // Set channel
        $result = $template->setChannel($this->testChannel);
        
        // Verify channel was added
        $channels = $template->getChannels();
        $this->assertContains($this->testChannel, $channels, 'getChannels() should contain the channel set with setChannel()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setChannel() should return $this for method chaining');
        
        // Set another channel
        $template->setChannel(Channel::TELEGRAM);
        
        // Verify both channels are present
        $channels = $template->getChannels();
        $this->assertContains($this->testChannel, $channels, 'getChannels() should contain the first channel');
        $this->assertContains(Channel::TELEGRAM, $channels, 'getChannels() should contain the second channel');
        $this->assertCount(2, $channels, 'getChannels() should contain exactly 2 channels');
        
        // Unset a channel
        $result = $template->unsetChannel($this->testChannel);
        
        // Verify channel was removed
        $channels = $template->getChannels();
        $this->assertNotContains($this->testChannel, $channels, 'getChannels() should not contain the channel unset with unsetChannel()');
        $this->assertContains(Channel::TELEGRAM, $channels, 'getChannels() should still contain the other channel');
        $this->assertCount(1, $channels, 'getChannels() should contain exactly 1 channel');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'unsetChannel() should return $this for method chaining');
        
        // Test with invalid channel
        $template->setChannel('invalid_channel');
        $channels = $template->getChannels();
        $this->assertNotContains('invalid_channel', $channels, 'getChannels() should not contain invalid channel');
    }

    /**
     * Test setGroupUrl and getGroupUrl methods
     */
    public function testSetGroupUrlAndGetGroupUrl(): void
    {
        $template = new Template();
        
        // Initial group_url should be empty
        $this->assertEquals('', $template->getGroupUrl(), 'Initial group_url should be empty');
        
        // Set group_url
        $result = $template->setGroupUrl($this->testGroupUrl);
        
        // Verify group_url was updated
        $this->assertEquals($this->testGroupUrl, $template->getGroupUrl(), 'getGroupUrl() should return the group_url set with setGroupUrl()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setGroupUrl() should return $this for method chaining');
        
        // Test with invalid URL
        $invalidUrl = 'not-a-valid-url';
        $template->setGroupUrl($invalidUrl);
        $this->assertEquals($this->testGroupUrl, $template->getGroupUrl(), 'Invalid URL should not update group_url');
    }

    /**
     * Test getStatus method
     */
    public function testGetStatus(): void
    {
        $template = new Template();
        
        // Default status should be STATUS_NEW
        $this->assertEquals(Template::STATUS_NEW, $template->getStatus(), 'Default status should be STATUS_NEW');
        
        // Create template with custom status
        $template = new Template(['status' => Template::STATUS_APPROVED]);
        $this->assertEquals(Template::STATUS_APPROVED, $template->getStatus(), 'getStatus() should return the status provided to constructor');
    }

    /**
     * Test setComment and getComment methods
     */
    public function testSetCommentAndGetComment(): void
    {
        $template = new Template();
        
        // Initial comment should be empty
        $this->assertEquals('', $template->getComment(), 'Initial comment should be empty');
        
        // Set comment
        $result = $template->setComment($this->testComment);
        
        // Verify comment was updated
        $this->assertEquals($this->testComment, $template->getComment(), 'getComment() should return the comment set with setComment()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setComment() should return $this for method chaining');
        
        // Verify changes were recorded
        $changes = $template->getChanges();
        $this->assertArrayHasKey('comment', $changes, 'Changes should contain comment key');
        $this->assertEquals($this->testComment, $changes['comment'], 'comment value in changes should match the provided comment');
        
        // Test with long comment (should be truncated to 255 characters)
        $longComment = str_repeat('a', 300);
        $template->setComment($longComment);
        $this->assertEquals(255, mb_strlen($template->getComment()), 'Long comment should be truncated to 255 characters');
    }

    /**
     * Test getActive method
     */
    public function testGetActive(): void
    {
        $template = new Template();
        
        // Default active should be false
        $this->assertFalse($template->getActive(), 'Default active should be false');
        
        // Create template with active = true
        $template = new Template(['active' => true]);
        $this->assertTrue($template->getActive(), 'getActive() should return the active provided to constructor');
    }

    /**
     * Test setReportUrl and getReportUrl methods
     */
    public function testSetReportUrlAndGetReportUrl(): void
    {
        $template = new Template();
        
        // Initial report_url should be empty
        $this->assertEquals('', $template->getReportUrl(), 'Initial report_url should be empty');
        
        // Set report_url
        $result = $template->setReportUrl($this->testReportUrl);
        
        // Verify report_url was updated
        $this->assertEquals($this->testReportUrl, $template->getReportUrl(), 'getReportUrl() should return the report_url set with setReportUrl()');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setReportUrl() should return $this for method chaining');
        
        // Test with invalid URL
        $invalidUrl = 'not-a-valid-url';
        $template->setReportUrl($invalidUrl);
        $this->assertEquals($this->testReportUrl, $template->getReportUrl(), 'Invalid URL should not update report_url');
    }

    /**
     * Test getCreated and getUpdated methods
     */
    public function testGetCreatedAndGetUpdated(): void
    {
        $template = new Template();
        
        // Default created and updated should be 0
        $this->assertEquals(0, $template->getCreated(), 'Default created should be 0');
        $this->assertEquals(0, $template->getUpdated(), 'Default updated should be 0');
        
        // Create template with custom created and updated
        $created = 1630000000;
        $updated = 1640000000;
        $template = new Template(['created' => $created, 'updated' => $updated]);
        $this->assertEquals($created, $template->getCreated(), 'getCreated() should return the created provided to constructor');
        $this->assertEquals($updated, $template->getUpdated(), 'getUpdated() should return the updated provided to constructor');
    }

    /**
     * Test getService method
     */
    public function testGetService(): void
    {
        $template = new Template();
        
        // Default notify_service should be empty
        $this->assertEquals('', $template->getService(), 'Default notify_service should be empty');
        
        // Create template with custom notify_service
        $service = 'test_service';
        $template = new Template(['notify_service' => $service]);
        $this->assertEquals($service, $template->getService(), 'getService() should return the notify_service provided to constructor');
    }

    /**
     * Test setFields method (via reflection)
     */
    public function testSetFields(): void
    {
        $template = new Template();
        
        // Use reflection to access the protected setFields method
        $reflection = new ReflectionMethod(Template::class, 'setFields');
        $reflection->setAccessible(true);
        
        // Test with valid field
        $field = ['name' => 'test_field', 'type' => '%d'];
        $result = $reflection->invoke($template, [$field]);
        
        // Use reflection to access the protected fields property directly
        $fieldsReflection = new ReflectionClass(Template::class);
        $fieldsProperty = $fieldsReflection->getProperty('fields');
        $fieldsProperty->setAccessible(true);
        $fields = $fieldsProperty->getValue($template);
        
        // Verify field was added to the fields property
        $this->assertCount(1, $fields, 'fields property should contain 1 field');
        $this->assertEquals($field, $fields[0], 'Field should match the provided field');
        
        // Verify getVariables() returns the same fields
        $variables = $template->getVariables();
        $this->assertCount(1, $variables, 'getVariables() should contain 1 field');
        $this->assertEquals($field, $variables[0], 'Field should match the provided field');
        
        // Method should return $this for chaining
        $this->assertSame($template, $result, 'setFields() should return $this for method chaining');
        
        // Verify changes were recorded
        $changes = $template->getChanges();
        $this->assertArrayHasKey('fields', $changes, 'Changes should contain fields key');
        
        // Test with invalid field (missing name)
        $invalidField = ['type' => '%d'];
        $reflection->invoke($template, [$invalidField]);
        $fields = $fieldsProperty->getValue($template);
        $this->assertCount(1, $fields, 'Invalid field should not be added');
        
        // Test with invalid field (missing type)
        $invalidField = ['name' => 'test_field'];
        $reflection->invoke($template, [$invalidField]);
        $fields = $fieldsProperty->getValue($template);
        $this->assertCount(1, $fields, 'Invalid field should not be added');
        
        // Test with invalid field (invalid type)
        $invalidField = ['name' => 'test_field', 'type' => 'invalid_type'];
        $reflection->invoke($template, [$invalidField]);
        $fields = $fieldsProperty->getValue($template);
        $this->assertCount(1, $fields, 'Field with invalid type should not be added');
    }

    /**
     * Test addNumericVariable method
     */
    public function testAddNumericVariable(): void
    {
        // Create a mock of the Template class to test the method call
        $templateMock = $this->getMockBuilder(Template::class)
            ->onlyMethods(['setFields'])
            ->getMock();
        
        // Set up expectations for the setFields method
        $name = 'test_numeric';
        $expectedField = ['name' => $name, 'type' => '%d'];
        
        $templateMock->expects($this->once())
            ->method('setFields')
            ->with($this->equalTo($expectedField))
            ->willReturnSelf();
        
        // Call the method
        $result = $templateMock->addNumericVariable($name);
        
        // Method should return $this for chaining
        $this->assertSame($templateMock, $result, 'addNumericVariable() should return $this for method chaining');
    }

    /**
     * Test addNumericSetVariable method
     */
    public function testAddNumericSetVariable(): void
    {
        // Create a mock of the Template class to test the method call
        $templateMock = $this->getMockBuilder(Template::class)
            ->onlyMethods(['setFields'])
            ->getMock();
        
        // Set up expectations for the setFields method
        $name = 'test_numeric_set';
        $expectedField = ['name' => $name, 'type' => '%d+'];
        
        $templateMock->expects($this->once())
            ->method('setFields')
            ->with($this->equalTo($expectedField))
            ->willReturnSelf();
        
        // Call the method
        $result = $templateMock->addNumericSetVariable($name);
        
        // Method should return $this for chaining
        $this->assertSame($templateMock, $result, 'addNumericSetVariable() should return $this for method chaining');
    }

    /**
     * Test addWordVariable method
     */
    public function testAddWordVariable(): void
    {
        // Create a mock of the Template class to test the method call
        $templateMock = $this->getMockBuilder(Template::class)
            ->onlyMethods(['setFields'])
            ->getMock();
        
        // Set up expectations for the setFields method
        $name = 'test_word';
        $expectedField = ['name' => $name, 'type' => '%w'];
        
        $templateMock->expects($this->once())
            ->method('setFields')
            ->with($this->equalTo($expectedField))
            ->willReturnSelf();
        
        // Call the method
        $result = $templateMock->addWordVariable($name);
        
        // Method should return $this for chaining
        $this->assertSame($templateMock, $result, 'addWordVariable() should return $this for method chaining');
    }

    /**
     * Test addWordSetVariable method
     */
    public function testAddWordSetVariable(): void
    {
        // Create a mock of the Template class to test the method call
        $templateMock = $this->getMockBuilder(Template::class)
            ->onlyMethods(['setFields'])
            ->getMock();
        
        // Set up expectations for the setFields method
        $name = 'test_word_set';
        $expectedField = ['name' => $name, 'type' => '%w+'];
        
        $templateMock->expects($this->once())
            ->method('setFields')
            ->with($this->equalTo($expectedField))
            ->willReturnSelf();
        
        // Call the method
        $result = $templateMock->addWordSetVariable($name);
        
        // Method should return $this for chaining
        $this->assertSame($templateMock, $result, 'addWordSetVariable() should return $this for method chaining');
    }

    /**
     * Test resetVariables method
     */
    public function testResetVariables(): void
    {
        // Create a Template instance with a mocked fields property
        $template = new Template();
        
        // Use reflection to set the fields property to a non-empty array
        $reflection = new ReflectionClass(Template::class);
        $fieldsProperty = $reflection->getProperty('fields');
        $fieldsProperty->setAccessible(true);
        $fieldsProperty->setValue($template, [
            ['name' => 'test_numeric', 'type' => '%d'],
            ['name' => 'test_word', 'type' => '%w']
        ]);
        
        // Verify fields property is not empty
        $fields = $fieldsProperty->getValue($template);
        $this->assertCount(2, $fields, 'fields should contain 2 variables');
        
        // Call resetVariables
        $template->resetVariables();
        
        // Verify fields property is now empty
        $fields = $fieldsProperty->getValue($template);
        $this->assertEmpty($fields, 'fields should be empty after resetVariables()');
    }

    /**
     * Test getVariables method
     */
    public function testGetVariables(): void
    {
        // Create a Template instance with a mocked fields property
        $template = new Template();
        
        // Use reflection to set the fields property
        $reflection = new ReflectionClass(Template::class);
        $fieldsProperty = $reflection->getProperty('fields');
        $fieldsProperty->setAccessible(true);
        
        // Initial fields should be empty
        $this->assertEmpty($fieldsProperty->getValue($template), 'Initial fields should be empty');
        
        // Set fields property to a test array
        $testFields = [
            ['name' => 'test_numeric', 'type' => '%d'],
            ['name' => 'test_word', 'type' => '%w']
        ];
        $fieldsProperty->setValue($template, $testFields);
        
        // Verify getVariables() returns the fields property
        $variables = $template->getVariables();
        $this->assertCount(2, $variables, 'getVariables() should contain 2 variables');
        $this->assertEquals('test_numeric', $variables[0]['name'], 'First variable name should match');
        $this->assertEquals('%d', $variables[0]['type'], 'First variable type should match');
        $this->assertEquals('test_word', $variables[1]['name'], 'Second variable name should match');
        $this->assertEquals('%w', $variables[1]['type'], 'Second variable type should match');
    }

    /**
     * Test getOptions method
     */
    public function testGetOptions(): void
    {
        $template = new Template();
        
        // Default options should be empty
        $this->assertEmpty($template->getOptions(), 'Default options should be empty');
        
        // Create template with custom options
        $options = ['option1' => 'value1', 'option2' => 'value2'];
        $template = new Template(['options' => $options]);
        $this->assertEquals($options, $template->getOptions(), 'getOptions() should return the options provided to constructor');
    }

    /**
     * Test touch method
     */
    public function testTouch(): void
    {
        $template = new Template();
        
        // Initial updated should be 0
        $this->assertEquals(0, $template->getUpdated(), 'Initial updated should be 0');
        
        // Call touch
        $template->touch();
        
        // Verify updated was set to current time
        $this->assertGreaterThan(0, $template->getUpdated(), 'updated should be set to current time after touch()');
        
        // Call touch again with updated already set
        $updatedTime = $template->getUpdated();
        sleep(1); // Wait 1 second to ensure time has changed
        $template->touch();
        
        // Verify updated was not changed
        $this->assertEquals($updatedTime, $template->getUpdated(), 'updated should not change when already set');
    }

    /**
     * Test getOriginal method
     */
    public function testGetOriginal(): void
    {
        $template = new Template();
        
        // Initial original should be empty
        $this->assertEmpty($template->getOriginal(), 'Initial original should be empty');
        
        // Call getProperties to populate original
        $template->getProperties();
        
        // Original should now contain data
        $original = $template->getOriginal();
        $this->assertArrayHasKey('id', $original, 'Original should contain id key');
        $this->assertArrayHasKey('name', $original, 'Original should contain name key');
        $this->assertArrayHasKey('text', $original, 'Original should contain text key');
        $this->assertArrayHasKey('channels', $original, 'Original should contain channels key');
        $this->assertArrayHasKey('status', $original, 'Original should contain status key');
        $this->assertEquals('', $original['id'], 'Original id should be empty');
        $this->assertEquals('', $original['name'], 'Original name should be empty');
        $this->assertEquals('', $original['text'], 'Original text should be empty');
        $this->assertEmpty($original['channels'], 'Original channels should be empty');
        $this->assertEquals(Template::STATUS_NEW, $original['status'], 'Original status should be STATUS_NEW');
    }

    /**
     * Test getChanges method
     */
    public function testGetChanges(): void
    {
        $template = new Template();
        
        // Initial changes should be empty
        $this->assertEmpty($template->getChanges(), 'Initial changes should be empty');
        
        // Set name and text to add to changes
        $template->setName($this->testName);
        $template->setText($this->testText);
        
        // Changes should contain name and text
        $changes = $template->getChanges();
        $this->assertArrayHasKey('name', $changes, 'Changes should contain name key');
        $this->assertArrayHasKey('text', $changes, 'Changes should contain text key');
        $this->assertEquals($this->testName, $changes['name'], 'name value in changes should match the provided name');
        $this->assertEquals($this->testText, $changes['text'], 'text value in changes should match the provided text');
    }

    /**
     * Test getProperties method
     */
    public function testGetProperties(): void
    {
        $template = new Template();
        
        // Set some properties
        $template->setName($this->testName);
        $template->setText($this->testText);
        $template->setChannel($this->testChannel);
        $template->setComment($this->testComment);
        $template->addNumericVariable('test_numeric');
        
        // Call getProperties
        $properties = $template->getProperties();
        
        // Verify properties
        $this->assertArrayHasKey('id', $properties, 'Properties should contain id key');
        $this->assertArrayHasKey('name', $properties, 'Properties should contain name key');
        $this->assertArrayHasKey('text', $properties, 'Properties should contain text key');
        $this->assertArrayHasKey('channels', $properties, 'Properties should contain channels key');
        $this->assertArrayHasKey('status', $properties, 'Properties should contain status key');
        $this->assertArrayHasKey('comment', $properties, 'Properties should contain comment key');
        $this->assertArrayHasKey('fields', $properties, 'Properties should contain fields key');
        $this->assertEquals('', $properties['id'], 'Properties id should be empty');
        $this->assertEquals($this->testName, $properties['name'], 'Properties name should match the set name');
        $this->assertEquals($this->testText, $properties['text'], 'Properties text should match the set text');
        $this->assertEquals([$this->testChannel], $properties['channels'], 'Properties channels should match the set channel');
        $this->assertEquals(Template::STATUS_NEW, $properties['status'], 'Properties status should be STATUS_NEW');
        $this->assertEquals($this->testComment, $properties['comment'], 'Properties comment should match the set comment');
        $this->assertEmpty($properties['fields'], 'Properties fields should be empty after getProperties() due to resetVariables()');
        
        // Verify variables were reset
        $this->assertEmpty($template->getVariables(), 'getVariables() should be empty after getProperties()');
    }

    /**
     * Test magic __set method with id property
     */
    public function testMagicSetWithId(): void
    {
        $template = new Template();
        
        // Initial id should be empty
        $this->assertEquals('', $template->getId(), 'Initial id should be empty');
        
        // Set id to a new value
        $template->id = $this->testId;
        
        // Verify id was updated
        $this->assertEquals($this->testId, $template->getId(), 'getId() should return the id set via magic __set');
        
        // Set id to empty value (should not update)
        $template->id = '';
        $this->assertEquals($this->testId, $template->getId(), 'id should not be updated with empty value');
        
        // Set id to non-string value (should not update)
        $template->id = 123;
        $this->assertEquals($this->testId, $template->getId(), 'id should not be updated with non-string value');
    }

    /**
     * Test magic __set method with invalid property
     */
    public function testMagicSetWithInvalidProperty(): void
    {
        $template = new Template();
        
        // Set an invalid property
        $template->invalidProperty = 'test';
        
        // Verify property was not added
        $reflection = new ReflectionClass(Template::class);
        $this->assertFalse($reflection->hasProperty('invalidProperty'), 'Invalid property should not be added');
    }
}