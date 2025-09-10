<?php

namespace Imobis\Sdk\Core;

use Imobis\Sdk\Config;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Logger;
use Imobis\Sdk\ValueObject\MessageMetadata;

abstract class Message
{
    public string $signature = '';
    protected string $id = '';
    protected string $customId = '';
    protected string $phone = '';
    protected string $text = '';
    protected array $message = [];
    protected array $prepared = [];
    protected MessageMetadata $metadata;
    protected float $price = 0.0;
    protected int $parts = 0;
    protected ?Status $status;

    public function __construct(string $phone, string $text, MessageMetadata $metadata)
    {
        $this->phone = $phone;
        $this->text = $text;
        $this->metadata = $metadata;
        $this->build();
    }

    abstract protected function prepare();
    abstract protected function maxTtl(): int;

    public function getId(): string
    {
        return $this->id;
    }

    public function setCustomId(string $customId): void
    {
        $this->prepared['custom_id'] = $this->customId = $customId;
    }

    public function getCustomId(): string
    {
        return $this->customId;
    }
    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getMetadata(): MessageMetadata
    {
        return $this->metadata;
    }

    public function getMessage(): array
    {
        return $this->message;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }

    public function setStatus(?Status $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    private function build()
    {
        if ($this->check()) {
            $this->message = array_merge($this->metadata->toArray(), $this->prepared, array_filter($this->prepare()));
            $this->signature = hash('sha1', implode('#', $this->metadata->toArray()));
        }
        else {
            throw new \Exception('Check failed');
        }
    }

    protected function checkPhone()
    {
        $this->phone = preg_replace('/[^0-9]/', '', $this->phone);

        if (strlen($this->phone) == 0) {
            throw new \Exception("Wrong phone number [$this->phone]");
        }
        else {
            $this->prepared['phone'] = $this->phone;
        }
    }

    protected function convertText()
    {
        if (strlen($this->text) == 0) {
            throw new \Exception("The text of the message is empty");
        }
        else {
            $this->prepared['text'] = mb_convert_encoding($this->text, "UTF-8", mb_detect_encoding($this->text));
        }
    }

    protected function adjustTtl()
    {
        if ($this->metadata->ttl() <= 0) {
            $this->metadata->ttl = Config::DEFAULT_TTL;
        }
        else if ($this->metadata->ttl() > $this->maxTtl()) {
            $this->metadata->ttl = $this->maxTtl();
        }
    }

    protected function check(): bool
    {
        try {
            $this->checkPhone();
            $this->convertText();
            $this->adjustTtl();
        } catch (\Exception $e) {
            $hash = Logger::log('debug', $e->getMessage(), $e->getTrace());
        } finally {
            return !isset($hash);
        }
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getParts(): int
    {
        return $this->parts;
    }

    public function __set($name, $value)
    {
        if ((($name === 'price' && is_float($value)) || ($name === 'parts' && is_int($value))) && !empty($value)) {
            $this->$name = $value;
        } elseif ($name === 'id' && preg_match(Config::UUID_REGEX, $id = trim($value))) {
            $this->$name = $id;
        }
    }
}
