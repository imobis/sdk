<?php

namespace Nexus\Message\Sdk\Exceptions;

use Nexus\Message\Sdk\Entity\Token;

class TokenException extends \Exception
{
    public function __construct(Token $token)
    {
        $apiKey = $token->getToken();
        parent::__construct("The token ($apiKey) is not valid.");
    }
}