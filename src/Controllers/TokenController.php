<?php

namespace Imobis\Sdk\Controllers;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Contract\Reading;

class TokenController extends Controller implements Reading
{
    protected string $model = \Imobis\Sdk\Entity\Token::class;
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