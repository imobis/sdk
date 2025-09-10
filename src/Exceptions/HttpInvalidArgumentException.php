<?php

namespace Imobis\Sdk\Exceptions;

class HttpInvalidArgumentException extends \InvalidArgumentException
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}