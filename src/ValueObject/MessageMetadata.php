<?php

namespace Nexus\Message\Sdk\ValueObject;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Traits\Arrayable;

class MessageMetadata
{
    use Arrayable;

    public string $report;
    public string $reply;
    public int $ttl;
    private bool $daydelivery;
    private string $delivery_date;

    private function __construct(string $report, string $reply = '', int $ttl = Config::DEFAULT_TTL, bool $day_delivery = false, string $delivery_date = '')
    {
        $this->report = $report;
        $this->reply = $reply;
        $this->ttl = $ttl;
        $this->daydelivery = $day_delivery;
        $this->delivery_date = $delivery_date;
        $this->checkUrl();
        $this->convertDeliveryDate();
    }

    public static function create(string $report, string $reply = '', int $ttl = Config::DEFAULT_TTL, bool $day_delivery = false, string $delivery_date = ''): MessageMetadata
    {
        return new self($report, $reply, $ttl, $day_delivery, $delivery_date);
    }

    public function reportUrl(): string
    {
        return $this->report;
    }

    public function replyUrl(): string
    {
        return $this->reply;
    }

    public function ttl(): int
    {
        return $this->ttl;
    }

    public function dayDelivery(): bool
    {
        return $this->daydelivery;
    }

    public function deliveryDate(): string
    {
        return $this->delivery_date;
    }

    private function checkUrl()
    {
        $filtered = filter_var_array(['report' => $this->report, 'reply' => $this->reply], FILTER_VALIDATE_URL);
        if (is_array($filtered)) {
            foreach ($filtered as $var_name => $value) {
                $this->{$var_name} = $value === false ? '' : $value;
            }
        }
    }

    private function convertDeliveryDate()
    {
        if ($delivery_date = strtotime($this->delivery_date)) {
            $this->delivery_date = date('Y-m-d H:i:s', $delivery_date);
        }
        else {
            $this->delivery_date = '';
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'report':
            case 'reply':
                $this->$name = $value;
                break;
            case 'ttl':
                if ($name === 'ttl' && $this->ttl >= Config::MIN_TTL && $this->ttl <= Config::MAX_TTL) {
                    $this->ttl = $value;
                }
                break;
        }
    }
}