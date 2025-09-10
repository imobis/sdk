<?php

namespace Imobis\Sdk\Core\Collections;

use Imobis\Sdk\Entity\Status;

class HybridRouteCollection extends RouteCollection
{
    protected int $count = 3;
    protected ?Status $status = null;

    public function getQueryData(): array
    {
        $metadata = [];
        $routes = [];

        foreach ($this->all() as $message) {
            $channel = $this->getMessageChannel($message);
            $metadata[$channel] = $message->getMetadata()->toArray();
            $routes[] = array_merge(
                ['channel' => $channel],
                array_diff_assoc($message->getMessage(), $message->getMetadata()->toArray())
            );
        }

        $data = array_shift($metadata);
        $data['route'] = $routes;

        return $data;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): HybridRouteCollection
    {
        $this->status = $status;
        $this->injection();

        return $this;
    }

    protected function injection(): void
    {
        if (method_exists($this->status, 'getEntityId')) {
            foreach ($this->items as $item) {
                $item->setStatus($this->getStatus());
                $item->id = $this->getStatus()->getEntityId();
            }
        }
    }
}