<?php

namespace Imobis\Sdk\Entity;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Contract\Entity;
use Imobis\Sdk\Core\Traits\Integrity;

class Token implements Entity
{
    use Integrity;

    protected string $login = '';
    protected string $token = '';
    protected string $category = '';
    private bool $active = false;
    private array $original = [];
    private array $changes = [];

    public function __construct(string $token, string $category = 'smsm')
    {
        $this->token = $token;
        $this->category = $category;
    }

    public function validate(): bool
    {
        return (bool)preg_match(Config::UUID_REGEX, trim($this->token));
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function touch()
    {
        if (!empty($this->login)) {
            $this->changes['active'] = $this->active = $this->validate();
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
            'login' => $this->getLogin(),
            'token' => $this->getToken(),
            'category' => $this->getCategory(),
            'active' => $this->getActive(),
        ];
    }

    public function __set($name, $value)
    {
        if ($name === 'login' && !empty($value) && is_string($value) && $this->validate()) {
            $this->changes[$name] = $this->$name = $value;
        }
    }
}