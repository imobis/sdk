<?php

namespace Imobis\Sdk\Core\Traits;

trait Arrayable {
    public function toArray(): array
    {
        return array_filter(get_object_vars($this));
    }
}