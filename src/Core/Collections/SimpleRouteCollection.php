<?php

namespace Nexus\Message\Sdk\Core\Collections;

use Nexus\Message\Sdk\Entity\Status;

class SimpleRouteCollection extends RouteCollection
{
    protected int $count = 1;
    protected ?Status $status = null;

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): SimpleRouteCollection
    {
        $this->status = $status;
        $this->injection();

        return $this;
    }

    protected function injection(): void
    {
        if (method_exists($this->status, 'getEntityId')) {
            foreach ($this->items as $item) {
                $item->id = $this->status->getEntityId();
                $item->setStatus($this->getStatus());
            }
        }
    }
}