<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Message;
use Imobis\Sdk\ValueObject\MessageMetadata;

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