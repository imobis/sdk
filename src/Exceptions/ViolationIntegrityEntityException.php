<?php

namespace Nexus\Message\Sdk\Exceptions;

use Nexus\Message\Sdk\Core\Contract\Entity;

class ViolationIntegrityEntityException extends \Exception
{
    public function __construct(Entity $entity)
    {
        parent::__construct(
            "The integrity of the object is violated: " . get_class($entity)
        );
    }
}