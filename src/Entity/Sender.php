<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Core\Contract\Entity;
use Imobis\Sdk\Core\Traits\Integrity;

class Sender implements Entity
{
    use Integrity;

    protected string $sender;
    protected string $channel;
    protected bool $checked = false;
    private array $original = [];
    private array $changes = [];

    public function __construct(string $sender = '', string $channel = '')
    {
        $this->sender = $sender;
        $this->channel = $channel;
    }

    public function checked(): bool
    {
        return $this->checked;
    }

    public function getSender(): string
    {
        return $this->sender;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function touch()
    {
        $this->changes['checked'] = $this->checked = true;
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function getProperties(): array
    {
        return $this->original = [
            'sender' => $this->getSender(),
            'channel' => $this->getChannel(),
            'checked' => $this->checked(),
        ];
    }
}