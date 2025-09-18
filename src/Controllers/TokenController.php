<?php

namespace Nexus\Message\Sdk\Controllers;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Contract\Reading;

class TokenController extends Controller implements Reading
{
    protected string $model = \Nexus\Message\Sdk\Entity\Token::class;
    protected string $mode = Config::DATA_MODE;

    public function read(?Collection $collection = null, array $filters = []): array
    {
        $data = $this->getData(__FUNCTION__, $filters);
        $properties = $this->token->getProperties();

        if (isset($data['login']) && $data['login'] !== $properties['login']) {
            $this->token->login = $data['login'];
            $this->token->touch();
        }

        return $data;
    }
}