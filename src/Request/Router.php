<?php

namespace Imobis\Sdk\Request;

use Imobis\Sdk\Config;
use Imobis\Sdk\Core\Collections\Collection;
use Imobis\Sdk\Entity\Token;
use Imobis\Sdk\Exceptions\HttpInvalidArgumentException;
use Imobis\Sdk\ValueObject\RequestData;

class Router
{
    protected const ENTITIES = [
        \Imobis\Sdk\Entity\Template::class => [
            'read' => [
                'route' => 'template/show',
                'method' => 'GET',
                'validation' => [
                    'name' => 'string|filled',
                    'options' => 'array|sometimes|min:1',
                    'options.id' => 'uuid|filled',
                    'options.text' => 'string|filled',
                    'options.group_url' => 'url',
                    'options.status' => 'string|filled',
                    'options.channel' => 'string|filled',
                    'options.active' => 'integer',
                ],
                'response_key' => 'data'
            ],
            'create' => [
                'route' => 'template/create',
                'method' => 'POST',
                'validation' => [
                    'name' => 'string|filled',
                    'text' => 'required|string|filled',
                    'fields' => 'array|sometimes|min:1',
                    'fields.*.name' => 'required_with:fields|string|filled',
                    'fields.*.type' => 'required_with:fields|string|filled',
                    'channels' => 'required|array|min:1',
                    'group_url' => 'url',
                    'report_url' => 'url',
                    'comment' => 'string|filled',
                ]
            ],
            'update' => [
                'route' => 'template/change',
                'method' => 'POST',
                'validation' => [
                    'id' => 'string|filled',
                    'options' => 'array|sometimes|min:1',
                    'options.text' => 'string|filled',
                    'options.name' => 'string|filled',
                    'options.comment' => 'string|filled',
                    'fields' => 'array|sometimes|min:1',
                    'fields.*.name' => 'required_with:fields|string|filled',
                    'fields.*.type' => 'required_with:fields|string|filled'
                ],
                'response_key' => 'change'
            ],
            'delete' => [
                'route' => 'template/delete',
                'method' => 'POST',
                'validation' => [
                    'id' => 'string|filled',
                ]
            ],
        ],
        \Imobis\Sdk\Entity\Balance::class => [
            'read' => [
                'route' => 'balance',
                'method' => 'POST',
            ],
        ],
        \Imobis\Sdk\Entity\Phone::class => [
            'read' => [
                'route' => 'check/phone',
                'method' => 'POST',
                'validation' => [
                    'phones' => 'required|array|distinct|max:500',
                ],
                'response_key' => 'data'
            ],
        ],
        \Imobis\Sdk\Entity\Sender::class => [
            'read' => [
                'route' => 'senders',
                'method' => 'POST',
                'validation' => [
                    'channel' => 'required|string|in:sms,all',
                ],
                'response_key' => 'senders'
            ],
        ],
        \Imobis\Sdk\Entity\Token::class => [
            'read' => [
                'route' => 'info',
                'method' => 'POST',
            ],
        ],
        \Imobis\Sdk\Core\Message::class => [
            'hydrid' => [
                'route' => 'message/send',
                'method' => 'POST',
                'validation' => [
                    'text' => 'required|string|filled',
                ],
                'response_key' => ''
            ],
            'simple' => [
                'route' => 'message/sendSimple',
                'method' => 'POST',
                'validation' => [
                    'text' => 'required|string|filled',
                ],
                'response_key' => ''
            ],
            'mixed' => [
                'route' => 'message/sendMixed',
                'method' => 'POST',
                'validation' => [
                    'text' => 'required|string|filled',
                ],
                'response_key' => ''
            ],
            'channel' => [
                'route' => 'message',
                'method' => 'POST',
                'options' => [
                    'sms' => 'message/sendSMS',
                    'telegram' => 'message/sendTelegram',
                    'viber' => 'message/sendViber',
                    'vk' => 'message/sendVK',
                ],
                'validation' => [
                    'text' => 'required|string|filled',
                ],
                'response_key' => ''
            ],
        ],
    ];

    public static function getResponse(Token $token, string $entity, string $action, array $data = [], array $options = []): array
    {
        $request = self::ENTITIES[$entity][$action] ?? null;

        if (isset($request['options']) && count($options) > 0) {
            foreach ($options as $field => $value) {
                if (isset($request[$field], $request['options'][$value])) {
                    $request[$field] = $request['options'][$value];
                }
            }
        }

        $client = new HttpClient($token);

        return isset($request['route'], $request['method']) ?
            $client->request($request['route'], RequestData::create($data, $request['method'])) : [];
    }

    public static function parseResponse(array $response, string $model, string $action, string $mode, ?Collection $collection = null)
    {
        $code = $response['code'] ?? null;
        $response = $response['body'][self::ENTITIES[$model][$action]['response_key'] ?? null] ?? $response['body'];

        if (isset($response['desc']) && $code === 400) {
            if (is_array($response['desc'])) {
                $message = implode(PHP_EOL, array_filter(array_map(function($v) {
                    return implode(PHP_EOL, array_filter($v));
                }, $response['desc'])));
            } else {
                $message = (string) $response['desc'];
            }

            throw new HttpInvalidArgumentException($message, $code);
        }

        if ($mode === Config::COLLECTION_MODE) {
            $properties = [];
            $reflection = new \ReflectionClass($model);
            foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
                $properties[] = $property->getName();
            }

            if (count(array_intersect_key($response[0] ?? $response, array_flip($properties))) > 0) {
                if (!is_null($collection)) {
                    $params = $reflection->getConstructor()->getParameters();
                    $count = count($params);
                    if (isset($response[0])) {
                        array_map(function($item) use ($collection, $model, $count) {
                            $collection->addObject(
                                $count > 1 ? new $model(...$item) : new $model($item)
                            );
                            $collection->last()->touch();
                        }, $response);
                    } else {
                        $collection->addObject(
                            $count > 1 ? new $model(...$response) : new $model($response)
                        );
                    }

                    $response = $collection;
                } else {
                    $response = isset($response[0]) ?
                        array_map(fn($item) => new $model($item), $response) : [new $model($response)];
                }
            }
        }

        return $response;
    }

    public static function getValidationRules(string $model, string $action): array
    {
        return self::ENTITIES[$model][$action]['validation'] ?? [];
    }
}