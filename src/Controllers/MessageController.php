<?php

namespace Imobis\Sdk\Controllers;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\ChannelRouteCollection;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Core\Collections\HybridRouteCollection;
use Imobis\Sdk\Core\Collections\MixedRouteCollection;
use Imobis\Sdk\Core\Collections\RouteCollection;
use Imobis\Sdk\Core\Collections\SimpleRouteCollection;
use Imobis\Sdk\Entity\Error;
use Imobis\Sdk\Entity\Status;
use Imobis\Sdk\Exceptions\SenderException;

class MessageController extends Controller
{
    protected string $model = \Imobis\Sdk\Core\Message::class;
    protected string $mode = Config::DATA_MODE;

    public function send(RouteCollection $collection): array
    {
        if ($collection instanceof ChannelRouteCollection) {
            return $this->channel($collection);
        } elseif ($collection instanceof SimpleRouteCollection) {
            return $this->simple($collection);
        } elseif ($collection instanceof HybridRouteCollection) {
            return $this->hydrid($collection);
        } elseif ($collection instanceof MixedRouteCollection) {
            return $this->mixed($collection);
        }

        return [];
    }

    protected function channel(ChannelRouteCollection $collection): array
    {
        return $this->request($collection,__FUNCTION__, ['route' => $collection->getChannel()]);
    }

    protected function simple(SimpleRouteCollection $collection): array
    {
        return $this->request($collection,__FUNCTION__);
    }

    protected function hydrid(HybridRouteCollection $collection): array
    {
        return $this->request($collection,__FUNCTION__);
    }

    protected function mixed(MixedRouteCollection $collection): array
    {
        return $this->request($collection,__FUNCTION__);
    }

    protected function request(RouteCollection $collection, string $action, array $options = []): array
    {
        $query_data = $collection->getQueryData();
        $response = $this->getData($action, $query_data, $options);

        if (isset($response['result'], $response['desc']) && $response['result'] === 'error') {
            if ($response['desc'] === 'Invalid sender') {
                throw new SenderException(
                    $query_data['sender'] ?? '',
                );
            }
        }

        $this->injectionStatuses($response, $collection);

        return $response;
    }

    protected function injectionStatuses(array $response, RouteCollection $routeCollection): Collection
    {
        $statusCollection = new Collection(Status::class);

        if ($routeCollection instanceof MixedRouteCollection) {
            $mixed = true;
            $routeCollection->setStatuses($statusCollection);
        }

        if (isset($response['result'], $response['id'], $response['status']) && $response['result'] === 'success') {
            $ids = isset($mixed) && is_array($response['id']) ? $response['id'] : [$response['id']];
            foreach ($ids as $id) {
                $statusCollection->addObject(
                    new Status($response['status'], $id)
                );
            }
        } else if (isset($response['result'], $response['desc']) && $response['result'] === 'error') {
            $error = new Error();
            $error->message = $response['desc'];
            $error->code = 1000;
            $status = new Status('error');
            $status->setError($error);
            $statusCollection->addObject($status);
        }

        if (
            $routeCollection instanceof ChannelRouteCollection ||
            $routeCollection instanceof SimpleRouteCollection ||
            $routeCollection instanceof HybridRouteCollection
        ) {
            $routeCollection->setStatus($statusCollection->first());
        } elseif (method_exists($routeCollection, 'postPrepare')) {
            $routeCollection->postPrepare();
        }

        return $statusCollection;
    }
}