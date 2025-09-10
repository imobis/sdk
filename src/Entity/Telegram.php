<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Message;
use Imobis\Sdk\ValueObject\MessageMetadata;

class Telegram extends Message
{
    public function __construct(string $phone, string $text, MessageMetadata $metadata)
    {
        parent::__construct($phone, $text, $metadata);
    }

    public function getVerificationCode($text): string
    {
        return filter_var($text, FILTER_SANITIZE_NUMBER_INT);
    }

    protected function prepare(): array
    {
        return [
            'text' => $this->getVerificationCode($this->text)
        ];
    }

    protected function maxTtl(): int
    {
        return Config::MIN_TTL;
    }
}