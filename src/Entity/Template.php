<?php

namespace Nexus\Message\Sdk\Entity;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Contract\Entity;
use Nexus\Message\Sdk\Core\Traits\Integrity;

class Template implements Entity
{
    use Integrity;

    public const STATUS_NEW = 'new';
    public const STATUS_SEND = 'send';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ERROR = 'error';
    public const STATUS_EXCEPTION = 'exception';
    public const STATUS_CANCEL = 'cancel';
    public const STATUS_DELETED = 'deleted';

    protected string $id = '';
    protected string $name = '';
    protected string $text = '';
    protected array $channel = [];
    protected string $group_url = '';
    protected string $status = self::STATUS_NEW;
    protected string $comment = '';
    protected string $notify_service = '';
    protected bool $active = false;
    protected string $report_url = '';
    protected int $created = 0;
    protected int $updated = 0;
    protected array $fields = [];
    protected array $options = [];
    private array $original = [];
    private array $changes = [];

    public function __construct(array $data = [])
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->setType($key, $value);
            }
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->changes['name'] = $this->name = mb_substr($name, 0, 255);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setText(string $text): self
    {
        $this->changes['text'] = $this->text = trim($text);

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function checkText(string $text): bool
    {
        return (bool) preg_match(Config::TEMPLATE_REGEX, trim($text));
    }

    public function setChannel(string $channel): self
    {
        $channel = strtolower($channel);
        switch ($channel) {
            case Channel::VK:
            case Channel::VIBER:
            case Channel::TELEGRAM:
            case Channel::SMS:
                $this->channel[$channel] = true;
                break;
        }

        return $this;
    }

    public function unsetChannel(string $channel): self
    {
        $channel = strtolower($channel);
        if (array_key_exists($channel, $this->channel)) {
            unset($this->channel[$channel]);
        }

        return $this;
    }

    public function getChannels(): array
    {
        return array_keys($this->channel);
    }

    public function setGroupUrl(string $group_url): self
    {
        if (filter_var($group_url, FILTER_VALIDATE_URL) !== false) {
            $this->group_url = $group_url;
        }

        return $this;
    }

    public function getGroupUrl(): string
    {
        return $this->group_url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setComment(string $comment): self
    {
        $this->changes['comment'] = $this->comment = mb_substr($comment, 0, 255);

        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setReportUrl(string $report_url): self
    {
        if (filter_var($report_url, FILTER_VALIDATE_URL) !== false) {
            $this->report_url = $report_url;
        }

        return $this;
    }

    public function getReportUrl(): string
    {
        return $this->report_url;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    protected function setFields(array $fields): self
    {
        foreach ($fields as $field) {
            if (isset($field['name'], $field['type']) && is_string($field['name']) && strlen($field['name']) > 0 && in_array($field['type'], ['%d', '%d+', '%w', '%w+'])) {
                $this->changes['fields'][] = $this->fields[] = $field;
            }
        }

        return $this;
    }

    public function addNumericVariable(string $name): self
    {
        $this->setFields(['name' => $name, 'type' => '%d']);

        return $this;
    }

    public function addNumericSetVariable(string $name): self
    {
        $this->setFields(['name' => $name, 'type' => '%d+']);

        return $this;
    }

    public function addWordVariable(string $name): self
    {
        $this->setFields(['name' => $name, 'type' => '%w']);

        return $this;
    }

    public function addWordSetVariable(string $name): self
    {
        $this->setFields(['name' => $name, 'type' => '%w+']);

        return $this;
    }

    public function resetVariables(): void
    {
        $this->fields = [];
    }

    public function getVariables(): array
    {
        return $this->fields;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getService(): string
    {
        return $this->notify_service;
    }

    protected function setType($key, $value): void
    {
        switch (true) {
            case $key === 'channel':
            case $key === 'fields':
            case $key === 'options':
                $this->$key = (array)$value;
                break;
            case $key === 'active':
                $this->$key = (bool)$value;
                break;
            case $key === 'created':
            case $key === 'updated':
                $this->$key = (int)$value;
                break;
            case $key === 'id':
            case $key === 'name':
            case $key === 'text':
            case $key === 'group_url':
            case $key === 'status':
            case $key === 'comment':
            case $key === 'notify_service':
            case $key === 'report_url':
                $this->$key = (string)$value;
                break;
            default:
                $this->$key = $value;
        }
    }

    public function getProperties(): array
    {
        $this->resetVariables();

        return $this->original = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'text' => $this->getText(),
            'channels' => $this->getChannels(),
            'group_url' => $this->getGroupUrl(),
            'status' => $this->getStatus(),
            'comment' => $this->getComment(),
            'notify_service' => $this->getService(),
            'active' => $this->getActive(),
            'report_url' => $this->getReportUrl(),
            'created' => $this->getCreated(),
            'updated' => $this->getUpdated(),
            'fields' => $this->getVariables(),
            'options' => $this->getOptions(),
        ];
    }

    public function touch()
    {
        if ($this->updated === 0) {
            $this->updated = time();
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

    public function __set($name, $value)
    {
        if ($name === 'id' && !empty($value) && is_string($value)) {
            $this->setType($name, $value);
        }
    }
}