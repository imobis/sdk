<?php

namespace Nexus\Message\Sdk\Core\Contract;

use Nexus\Message\Sdk\Core\Collections\Collection;

interface Reading
{
    public function read(?Collection $collection = null, array $filters = []);
}