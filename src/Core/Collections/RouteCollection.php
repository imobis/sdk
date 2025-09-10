<?php

namespace Imobis\Sdk\Core\Collections;

use Imobis\Sdk\Core\Message;
use Imobis\Sdk\Entity\Sms;
use Imobis\Sdk\Entity\Telegram;
use Imobis\Sdk\Entity\Viber;
use Imobis\Sdk\Entity\Vk;

abstract class RouteCollection extends Collection
{
    protected int $count = 0;
    protected array $channels = [];

    public function __construct()
    {
        parent::__construct(\Imobis\Sdk\Core\Message::class);
    }

    protected abstract function injection();

    public function addObject(object $item): bool
    {
        if ($item instanceof $this->class && $this->check($item)) {
            $this->items[] = $item;
            $this->addChannel($item);

            return true;
        }

        return false;
    }

    protected function addChannel(object $object): int
    {
        $class = get_class($object);

        return isset($this->channels[$class]) ? ++$this->channels[$class] : $this->channels[$class] = 1;
    }

    protected function check(object $object): bool
    {
        return !isset($this->channels[get_class($object)]) && $this->count() < $this->count;
    }

    public function getQueryData(): array
    {
        $message = $this->first();

        return method_exists($message, 'getMessage') ? $message->getMessage() : $this->all();
    }

    protected function getMessageChannel(Message $message): string
    {
        if ($message instanceof Sms) {
            $channel = 'sms';
        }
        else if ($message instanceof Telegram) {
            $channel = 'telegram';
        }
        else if ($message instanceof Viber) {
            $channel = 'viber';
        }
        else if ($message instanceof Vk) {
            $channel = 'vk';
        }

        return $channel ?? '';
    }
}