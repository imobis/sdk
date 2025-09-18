<?php

namespace Nexus\Message\Sdk\Controllers;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Contract\Reading;

class PhoneController extends Controller implements Reading
{
    protected string $model = \Nexus\Message\Sdk\Entity\Phone::class;
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