<?php

namespace Imobis\Sdk\Exceptions;

class ConnectionException extends \Exception
{
    public function __construct(string $url)
    {
        parent::__construct(
            'Не удалось установить подключение. URL: ' . $url
        );
    }
}