<?php

namespace Imobis\Sdk\Controllers;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Contract\Reading;

class BalanceController extends Controller implements Reading
{
    protected string $model = \Imobis\Sdk\Entity\Balance::class;
    protected string $mode = Config::DATA_MODE;

    public function read(?Collection $collection = null, array $filters = []): array
    {
        if ($this->token->getActive()) {
            $data = $this->getData(__FUNCTION__, $filters);
            $balance = \Imobis\Sdk\Entity\Balance::getInstance();
            $properties = $balance->getProperties();

            if (isset($data['balance'], $properties['balance']) && $data['balance'] !== $properties['balance']) {
                $balance->balance = $data['balance'];
                $balance->touch();
            }

            if (isset($data['currency'], $properties['currency']) && $data['currency'] !== $properties['currency']) {
                $balance->currency = $data['currency'];
            }
        }

        return $data ?? [];
    }
}