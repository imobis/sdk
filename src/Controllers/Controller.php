<?php

namespace Nexus\Message\Sdk\Controllers;

use Nexus\Message\Sdk\Config;
use Nexus\Message\Sdk\Core\Collections\Collection;
use Nexus\Message\Sdk\Entity\Token;
use Nexus\Message\Sdk\Request\Router;

abstract class Controller
{
    protected string $model = \Nexus\Message\Sdk\Core\Contract\Entity::class;
    protected Token $token;
    protected string $mode = Config::COLLECTION_MODE;
    protected array $filters = [];

    public function __construct(Token $token)
    {
        $this->token = $token;
    }

    protected function query(string $action, array $data = [], array $options = []): array
    {
        return Router::getResponse($this->token, $this->model, $action, $data, $options);
    }

    protected function getData(string $action, array $data = [], array $options = [], ?Collection $collection = null)
    {
        return Router::parseResponse(
            $this->query($action, $data, $options), $this->model, $action, $this->mode, $collection
        );
    }
}