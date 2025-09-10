<?php

namespace Imobis\Sdk\Exceptions;

class LowBalanceException extends \Exception
{
    public function __construct(float $balance = 0.0)
    {
        parent::__construct(
            'Не достаточно средств. Баланс: ' . $balance
        );
    }
}