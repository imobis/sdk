<?php

namespace Nexus\Message\Sdk\Core\Traits;

use Nexus\Message\Sdk\Exceptions\ViolationIntegrityEntityException;

trait Integrity {
    public function checkingIntegrityObject(array $rules = []): bool
    {
        if (!empty($rules)) {
            $attributes = [];

            foreach ($rules as $key => $value) {
                if (!is_string($value) || $value === '') {
                    continue;
                }

                $keys = array_keys(explode('|', $value), 'required');
                if (count($keys) > 0) {
                    $attributes[] = $key;
                }
            }

            if (count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    if (isset($this->$attribute) && empty($this->$attribute)) {
                        throw new ViolationIntegrityEntityException($this);
                    }
                }
            }
        }

        return true;
    }
}