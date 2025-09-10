<?php

namespace Imobis\Sdk\Exceptions;

use Imobis\Sdk\Core\Contract\Entity;

class ViolationIntegrityEntityException extends \Exception
{
    public function __construct(Entity $entity)
    {
        parent::__construct(
            "Нарушена целостность объекта: " . get_class($entity)
        );
    }
}