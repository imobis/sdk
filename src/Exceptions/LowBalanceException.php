<?php

namespace Nexus\Message\Sdk\Exceptions;

class LowBalanceException extends \Exception
{
    public function __construct(float $balance = 0.0)
    {
        parent::__construct(
            'There are not enough funds. Balance: ' . $balance
        );
    }
}