<?php

namespace Imobis\Sdk\Core\Contract;

use Imobis\Sdk\Core\Collections\Collection;

interface Reading
{
    public function read(?Collection $collection = null, array $filters = []);
}