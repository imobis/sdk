<?php

namespace Imobis\Sdk\Core\Collections;

class MixedRouteCollection extends RouteCollection
{
    protected int $count = 1000;
    protected ?Collection $statuses = null;

    public function getQueryData(): array
    {
        $routes = [];

        foreach ($this->all() as $message) {
            $signature = $message->getSignature();
            if (!isset($routes[$signature]['metadata'])) {
                $routes[$signature]['metadata'] = $message->getMetadata()->toArray();
            }

            $channel = $this->getMessageChannel($message);
            if (strlen($channel) === 0) {
                continue;
            }

            $routes[$signature]['route'][$channel] = array_merge(
                ['channel' => $channel],
                array_diff_assoc($message->getMessage(), $message->getMetadata()->toArray())
            );
        }

        $data = [];
        foreach ($routes as $signature => $item) {
            $data[$signature] = array_merge(['route' => array_values($item['route'])], $item['metadata']);
        }

        return array_values($data);
    }

    public function getStatuses(): ?Collection
    {
        return $this->statuses;
    }

    public function setStatuses(?Collection $statuses): MixedRouteCollection
    {
        $this->statuses = $statuses;

        return $this;
    }

    public function postPrepare(): void
    {
        $this->injection();
    }

    protected function injection(): void
    {
        if ($this->statuses === null) {
            return;
        }

        $statuses = $this->statuses->all();

        foreach ($this->all() as $key => $item) {
            if (isset($statuses[$key]) && method_exists($item, 'setStatus') && method_exists($statuses[$key], 'getEntityId')) {
                $item->id = $statuses[$key]->getEntityId();
                $item->setStatus($statuses[$key]);
            }
        }
    }
}