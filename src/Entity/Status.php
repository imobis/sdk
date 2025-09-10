<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Core\Contract\Entity;
use Imobis\Sdk\Core\Message;
use Imobis\Sdk\Core\Traits\Integrity;

class Status implements Entity
{
    use Integrity;

    public const LIST = [
        'accepted',
        'sent',
        'unknown',
        'rejected',
        'undelivered',
        'expired',
        'deleted',
        'delivered',
        'read',
        'error',
    ];

    protected ?Error $error;
    protected ?Channel $channel;
    protected string $entityId = '';
    protected string $entityClass = Message::class;
    protected string $status = '';
    public array $info = [];
    private array $original = [];
    private array $changes = [];

    public function __construct(?string $status = null, ?string $entityId = null)
    {
        $this->status = in_array($status, self::LIST) ? $status : 'unknown';
        if (!is_null($entityId)) {
            $this->entityId = $entityId;
        }
    }

    public function getError(): ?Error
    {
        return $this->error;
    }

    public function setError(Error $error): self
    {
        $this->error = $error;

        return $this;
    }

    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $class): bool
    {
        if ($class === Template::class) {
            $this->entityClass = $class;

            return true;
        }

        return false;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (in_array($status, self::LIST)) {
            $this->status = $status;
        }

        return $this;
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function touch()
    {
        if (isset($this->changes['info'])) {
            $this->changes['initialize'] = true;
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
        $channel = $this->getChannel();
        $error = $this->getError();

        return $this->original = [
            'entityId' => $this->getEntityId(),
            'entityClass' => $this->getEntityClass(),
            'status' => $this->getStatus(),
            'info' => $this->getInfo(),
            'channel' => is_object($channel) && method_exists($channel, 'toArray') ? $channel->toArray() : $channel,
            'error' => is_object($error) && method_exists($error, 'toArray') ? $error->toArray() : $error,
        ];
    }
}