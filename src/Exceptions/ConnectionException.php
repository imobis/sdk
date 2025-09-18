<?php

namespace Nexus\Message\Sdk\Exceptions;

class ConnectionException extends \Exception
{
    public function __construct(string $url)
    {
        parent::__construct(
            'Connection could not be established. URL: ' . $url
        );
    }
}