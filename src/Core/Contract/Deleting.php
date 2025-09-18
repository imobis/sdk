<?php

namespace Nexus\Message\Sdk\Core\Contract;

interface Deleting
{
    public function delete(Entity $entity);
}