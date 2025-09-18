<?php

namespace Nexus\Message\Sdk\Core\Contract;

interface Entity
{
    public function getProperties(): array;
    public function touch();
    public function getOriginal();
    public function getChanges();
    public function checkingIntegrityObject(array $rules = []): bool;
}