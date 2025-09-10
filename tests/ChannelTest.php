<?php

namespace Imobis\Sdk\Tests;

use Imobis\Sdk\Entity\Channel;
use PHPUnit\Framework\TestCase;

require_once 'vendor/autoload.php';

class ChannelTest extends TestCase
{
    /**
     * Test constructor with valid channel values
     * 
     * @dataProvider validChannelProvider
     */
    public function testConstructorWithValidChannel(string $channelValue): void
    {
        $channel = new Channel($channelValue);
        
        $this->assertInstanceOf(Channel::class, $channel, 'Constructor should return a Channel instance');
        $this->assertEquals($channelValue, $channel->getChannel(), 'getChannel() should return the channel value provided to constructor');
    }

    /**
     * Test constructor with invalid channel value
     */
    public function testConstructorWithInvalidChannel(): void
    {
        $channel = new Channel('invalid_channel');
        
        $this->assertInstanceOf(Channel::class, $channel, 'Constructor should return a Channel instance even with invalid channel');
        
        // The getChannel method is defined to return a string, but with an invalid channel value,
        // the channel property is null, which would cause a TypeError when getChannel() is called.
        // We expect a TypeError to be thrown.
        $this->expectException(\TypeError::class);
        $channel->getChannel();
    }

    /**
     * Test getChannel method
     * 
     * @dataProvider validChannelProvider
     */
    public function testGetChannel(string $channelValue): void
    {
        $channel = new Channel($channelValue);
        
        $result = $channel->getChannel();
        
        $this->assertEquals($channelValue, $result, 'getChannel() should return the channel value provided to constructor');
    }

    /**
     * Test toArray method from Arrayable trait
     * 
     * @dataProvider validChannelProvider
     */
    public function testToArray(string $channelValue): void
    {
        $channel = new Channel($channelValue);
        
        $result = $channel->toArray();
        
        $this->assertIsArray($result, 'toArray() should return an array');
        $this->assertArrayHasKey('channel', $result, 'toArray() result should contain channel key');
        $this->assertEquals($channelValue, $result['channel'], 'toArray() result should have correct channel value');
    }

    /**
     * Test toArray method with invalid channel value
     */
    public function testToArrayWithInvalidChannel(): void
    {
        $channel = new Channel('invalid_channel');
        
        $result = $channel->toArray();
        
        $this->assertIsArray($result, 'toArray() should return an array');
        $this->assertEmpty($result, 'toArray() result should be empty for invalid channel value');
    }

    /**
     * Data provider for valid channel values
     */
    public function validChannelProvider(): array
    {
        return [
            [Channel::VK],
            [Channel::VIBER],
            [Channel::TELEGRAM],
            [Channel::SMS]
        ];
    }
}