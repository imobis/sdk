<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Message;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;

class Sms extends Message
{
    private string $sender;
    public function __construct(string $sender, string $phone, string $text, MessageMetadata $metadata)
    {
        $this->sender = $sender;
        parent::__construct($phone, $text, $metadata);
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    protected function prepare(): array
    {
        return [
            'sender' => $this->sender
        ];
    }

    protected function maxTtl(): int
    {
        return Config::MAX_TTL;
    }
}