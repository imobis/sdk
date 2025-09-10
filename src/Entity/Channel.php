<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Core\Traits\Arrayable;

class Channel
{
    use Arrayable;

    public const VK = 'vk';
    public const VIBER = 'viber';
    public const TELEGRAM = 'telegram';
    public const SMS = 'sms';

    protected ?string $channel = null;

    public function __construct(string $channel)
    {
        if ($channel === self::VK || $channel === self::VIBER || $channel === self::TELEGRAM || $channel === self::SMS) {
            $this->channel = $channel;
        }
    }
    public function getChannel(): string
    {
        return $this->channel;
    }
}