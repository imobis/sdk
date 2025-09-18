<?php

namespace Nexus\Message\Sdk\Exceptions;

use Nexus\Message\Sdk\Core\Collections\Collection;

class CollectionException extends \Exception
{
    public function __construct(Collection $collection)
    {
        $class = get_class($collection);
        parent::__construct(
            "An empty collection of the $class class"
        );
    }
}