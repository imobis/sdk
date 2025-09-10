<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Core\Contract\Entity;
use Imobis\Sdk\Core\Traits\Integrity;

class Reply implements Entity
{
    use Integrity;

    protected string $messageId;
    protected ?string $customId;
    protected string $text;
    protected string $date;
    private array $original = [];
    private array $changes = [];

    public function __construct(string $messageId, string $text, string $date, ?string $customId = null)
    {
        $this->messageId = $messageId;
        $this->text = $text;
        $this->date = $date;
        $this->customId = $customId;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }

    public function touch()
    {
        if (!isset($this->changes['processed'])) {
            $this->changes['processed'] = time();
        }
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
            'messageId' => $this->getMessageId(),
            'customId' => $this->getCustomId(),
            'text' => $this->getText(),
            'date' => $this->getDate(),
        ];
    }
}