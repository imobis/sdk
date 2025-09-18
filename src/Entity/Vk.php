<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Message;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;

class Vk extends Message
{
    private int $group;
    public function __construct(int $group_id, string $phone, string $text, MessageMetadata $metadata)
    {
        $this->group = $group_id;
        parent::__construct($phone, $text, $metadata);
    }

    public function getGroup(): string
    {
        return $this->group;
    }

    protected function prepare(): array
    {
        return [
            'group' => $this->group
        ];
    }

    protected function maxTtl(): int
    {
        return Config::MEDIUM_TTL;
    }
}