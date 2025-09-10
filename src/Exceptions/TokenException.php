<?php

namespace Imobis\Sdk\Exceptions;

use Imobis\Sdk\Entity\Token;

class TokenException extends \Exception
{
    public function __construct(Token $token)
    {
        $apiKey = $token->getToken();
        parent::__construct("Токен ($apiKey) не действителен.");
    }
}