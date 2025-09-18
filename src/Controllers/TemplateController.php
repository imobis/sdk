<?php

namespace Nexus\Message\Sdk\Controllers;

use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Core\Contract\Creating;
use Nexus\Message\Sdk\Core\Contract\Deleting;
use Nexus\Message\Sdk\Core\Contract\Entity;
use Nexus\Message\Sdk\Core\Contract\Filtering;
use Nexus\Message\Sdk\Core\Contract\Reading;
use Nexus\Message\Sdk\Core\Contract\Updating;
use Nexus\Message\Sdk\Request\Router;

class TemplateController extends Controller implements Reading, Creating, Updating, Deleting, Filtering
{
    protected string $model = \Nexus\Message\Sdk\Entity\Template::class;

    public function read(?Collection $collection = null, array $filters = []): array
    {
        $this->filter($filters);
        $data = count($this->filters) ? ['options' => $this->filters] : [];
        if (isset($this->filters['name'])) {
            $data['name'] = $this->filters['name'];
        }

        $response = $this->getData(__FUNCTION__, $data, [], $collection);

        return $response instanceof Collection ? $response->all() : $response;
    }

    public function create(Entity $entity): array
    {
        $rules = Router::getValidationRules($this->model, __FUNCTION__);
        $entity->checkingIntegrityObject($rules);
        $data = array_filter($entity->getProperties());

        $response = $this->getData(__FUNCTION__, $data);

        if (isset($response[0]) && $response[0] instanceof Entity) {
            $created = array_filter($response[0]->getProperties());
            foreach ($created as $key => $value) {
                $entity->{$key} = $value;
            }
        }

        $entity->touch();

        return $response;
    }

    public function update(Entity $entity): array
    {
        $rules = Router::getValidationRules($this->model, __FUNCTION__);
        $entity->checkingIntegrityObject($rules);
        $data = ['id' => $entity->getId()];
        $changes = array_filter($entity->getChanges());

        if (isset($changes['fields']) && is_array($changes['fields']) && count($changes['fields'])) {
            $data['fields'] = $changes['fields'];
            unset($changes['fields']);
        }

        if (count($changes) > 0) {
            $data['options'] = $changes;
        }

        return $this->getData(__FUNCTION__, $data);
    }

    public function delete(Entity $entity): array
    {
        $rules = Router::getValidationRules($this->model, __FUNCTION__);
        $entity->checkingIntegrityObject($rules);

        return $this->getData(__FUNCTION__, ['id' => $entity->getId()]);
    }

    public function filter(array $filters): self
    {
        $allow_options = [
            'id' => 'string',
            'name' => 'string',
            'text' => 'string',
            'channel' => 'string',
            'group_url' => 'string',
            'status' => 'string',
            'active' => 'integer',
        ];
        foreach (array_intersect_key($filters, $allow_options) as $field => $value) {
            settype($value, $allow_options[$field]);
            $this->filters[$field] = $value;
        }

        return $this;
    }

    public function filterById(string $id): self
    {
        return $this->filter(['id' => $id]);
    }

    public function filterByName(string $name): self
    {
        return $this->filter(['name' => $name]);
    }

    public function filterByText(string $text): self
    {
        return $this->filter(['text' => $text]);
    }

    public function filterByChannel(string $channel): self
    {
        return $this->filter(['channel' => $channel]);
    }

    public function filterByGroupUrl(string $url): self
    {
        return $this->filter(['group_url' => $url]);
    }

    public function filterByStatus(string $status): self
    {
        return $this->filter(['status' => $status]);
    }

    public function filterByActive(bool $active): self
    {
        return $this->filter(['active' => $active]);
    }
}