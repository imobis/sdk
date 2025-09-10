<?php

namespace Imobis\Sdk\Exceptions;

use Imobis\Sdk\Core\Collections\Collection;

class CollectionException extends \Exception
{
    public function __construct(Collection $collection)
    {
        $class = get_class($collection);
        parent::__construct(
            "Пустая коллекция, класс $class"
        );
    }
}