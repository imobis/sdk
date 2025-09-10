<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Core\Contract\Entity;
use Imobis\Sdk\Core\Singleton;
use Imobis\Sdk\Core\Traits\Integrity;

class Sandbox extends Singleton implements Entity
{
    use Integrity;

    private bool $active = false;
    private array $original = [];
    private array $changes = [];
    private ?Token $token;

    protected function __construct(?Token $token = null)
    {
        parent::__construct();
        $this->token = $token;
    }

    public function token(): ?Token
    {
        return $this->token;
    }

    public function activate(): self
    {
        $this->active = true;

        return $this;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function touch()
    {
        $this->changes['active'] = $this->active();
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
            'active' => $this->active(),
            'token' => $this->token() instanceof Token ? $this->token()->getToken() : $this->token,
        ];
    }
}