<?php

namespace Imobis\Sdk\Controllers;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Contract\Reading;

class PhoneController extends Controller implements Reading
{
    protected string $model = \Imobis\Sdk\Entity\Phone::class;
    protected string $mode = Config::DATA_MODE;

    public function read(?Collection $collection = null, array $filters = []): array
    {
        if ($this->token->getActive()) {
            $phones = [];
            foreach ($collection as $phone) {
                if ($phone->needCheck()) {
                    $phones[] = $phone->getNumber();
                }
            }

            if (count($phones) > 0) {
                $data = $this->getData(__FUNCTION__, ['phones' => $phones]);
                $data = array_column($data, null, 'phone_source');
                foreach ($collection as $phone) {
                    if (isset($data[$phone->getNumber()])) {
                        $phone->setInfo(
                            array_intersect_key($data[$phone->getNumber()], $phone->getProperties()['info'] ?? [])
                        )->touch();
                    }
                }
            }
        }

        return $data ?? [];
    }
}