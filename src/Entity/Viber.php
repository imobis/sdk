<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Message;
use Nexus\Message\Sdk\ValueObject\MessageMetadata;

class Viber extends Message
{
    private string $sender;
    private string $image_url;
    private array $action;
    public function __construct(string $sender, string $phone, string $text, MessageMetadata $metadata, string $image_url = '', array $action = ['title' => '', 'url' => ''])
    {
        $this->sender = $sender;
        $this->image_url = $image_url;
        $this->action = $action;
        $this->checkAction();
        parent::__construct($phone, $text, $metadata);
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getImageUrl(): string
    {
        return $this->image_url;
    }

    public function getAction(): array
    {
        return $this->action;
    }

    private function checkAction()
    {
        if (!isset($this->action['title'], $this->action['url']) || filter_var($this->action['url'], FILTER_VALIDATE_URL) === false) {
            $this->action = [];
        }
    }

    protected function prepare(): array
    {
        return [
            'sender' => $this->sender,
            'image' => $this->image_url,
            'action' => $this->action,
        ];
    }

    protected function maxTtl(): int
    {
        return Config::MEDIUM_TTL;
    }
}