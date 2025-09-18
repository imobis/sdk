<?php

namespace Nexus\Message\Sdk\Exceptions;

class SenderException extends \Exception
{
    public function __construct(string $sender = '')
    {
        parent::__construct(
            empty($sender) ? "Sender's name is not specified" : "The sender's name $sender is not available"
        );
    }
}