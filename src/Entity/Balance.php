<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Contract\Entity;
use Nexus\Message\Sdk\Core\Singleton;
use Nexus\Message\Sdk\Core\Traits\Integrity;

class Balance extends Singleton implements Entity
{
    use Integrity;

    protected float $balance = 0.0;
    protected string $currency = Config::CURRENCY;
    private bool $fresh = false;
    private array $original = [];
    private array $changes = [];

    protected function __construct()
    {
        parent::__construct();
    }

    public function touch()
    {
        $this->changes['fresh'] = $this->fresh = true;
    }

    public function getOriginal(): array
    {
        return $this->original;
    }

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function isFresh(): bool
    {
        return $this->fresh;
    }

    public function getProperties(): array
    {
        return $this->original = [
            'balance' => $this->getBalance(),
            'fresh' => $this->isFresh(),
            'currency' => $this->getCurrency(),
        ];
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function __set($name, $value)
    {
        if ($name === 'balance' && !empty($value) && $this->$name != $value) {
            $this->changes[$name] = $this->$name = (float)$value;
        }
        else if ($name === 'currency' && !empty($value) && is_string($value) && $this->$name !== $value) {
            $this->changes[$name] = $this->$name = $value;
        }
    }
}