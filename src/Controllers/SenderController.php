<?php

namespace Nexus\Message\Sdk\Controllers;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Contract\Reading;
use Nexus\Message\Sdk\Entity\Sender;

class SenderController extends Controller implements Reading
{
    protected string $model = \Nexus\Message\Sdk\Entity\Sender::class;
    protected string $mode = Config::DATA_MODE;

    public function read(?Collection $collection = null, array $filters = []): array
    {
        if ($this->token->getActive()) {
            $data = $this->getData(__FUNCTION__, ['channel' => $filters['channel'] ?? 'all']);

            if (count($data) > 0) {
                foreach ($data as $channel => $senders) {
                    if (is_array($senders)) {
                        foreach ($senders as $sender) {
                            $collection->addObject(
                                new Sender($sender, $channel)
                            );
                            $collection->last()->touch();
                        }
                    } else {
                        $collection->addObject(
                            new Sender($senders, $filters['channel'] ?? '')
                        );
                        $collection->last()->touch();
                    }
                }
            }
        }

        return $data ?? [];
    }
}