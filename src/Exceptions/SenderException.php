<?php

namespace Imobis\Sdk\Exceptions;

class SenderException extends \Exception
{
    public function __construct(string $sender = '')
    {
        parent::__construct(
            empty($sender) ? 'Не задано имя отправителя' : "Имя отправителя $sender не доступно"
        );
    }
}