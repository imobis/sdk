<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Core\Contract\Entity;
use Nexus\Message\Sdk\Core\Traits\Integrity;

class Phone implements Entity
{
    use Integrity;

    protected bool $initialize = false;
    protected bool $check = true;
    protected string $number = '';
    protected array $info = [];
    private array $original = [];
    private array $changes = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                settype($value, gettype($this->$key));

                if (method_exists($this, 'set' . ucfirst($key)) && ($key === 'number' || $key === 'info')) {
                    $this->{'set' . ucfirst($key)}($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public function withoutCheck(): self
    {
        $this->check = false;

        return $this;
    }

    public function valid(): bool
    {
        return !$this->check || $this->getStatus() === 'ok';
    }

    public function touch()
    {
        if (isset($this->changes['info'])) {
            $this->changes['initialize'] = $this->initialize = true;
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
            'check' => $this->check,
            'number' => $this->getNumber(),
            'info' => [
                'phone_number' => $this->info['phone_number'] ?? '',
                'status' => $this->getStatus(),
                'country' => $this->getCountry(),
                'operator' => $this->getOperator(),
                'region_id' => $this->getRegionId(),
                'region_name' => $this->getRegionName(),
                'region_timezone' => $this->getTimezone()
            ]
        ];
    }

    public function setNumber(string $number): self
    {
        $this->number = self::parseNumber($number);
        $this->initialize = false;

        return $this;
    }

    public function setInfo(array $info): self
    {
        $this->changes['info'] = $this->info = $info;

        return $this;
    }

    public function needCheck(): bool
    {
        return $this->check;
    }

    public function checked(): bool
    {
        return $this->initialize;
    }

    public function getNumber(): string
    {
        return $this->info['phone_number'] ?? $this->number;
    }

    public function getStatus(): string
    {
        return $this->info['status'] ?? '';
    }

    public function getCountry(): string
    {
        return $this->info['country'] ?? '';
    }

    public function getOperator(): string
    {
        return $this->info['operator'] ?? '';
    }

    public function getRegionId(): string
    {
        return $this->info['region_id'] ?? '';
    }

    public function getRegionName(): string
    {
        return $this->info['region_name'] ?? '';
    }

    public function getTimezone(): string
    {
        return $this->info['region_timezone'] ?? '';
    }

    protected static function parseNumber($number)
    {
        return preg_replace('/[^0-9]/', '', $number);
    }
}