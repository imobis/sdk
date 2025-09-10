<?php

namespace Imobis\Sdk\Core\Contract;

interface Deleting
{
    public function delete(Entity $entity);
}