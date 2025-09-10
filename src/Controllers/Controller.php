<?php

namespace Imobis\Sdk\Controllers;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Entity\Token;
use Imobis\Sdk\Request\Router;

abstract class Controller
{
    protected string $model = \Imobis\Sdk\Core\Contract\Entity::class;
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