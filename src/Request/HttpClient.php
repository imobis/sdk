<?php

namespace Nexus\Message\Sdk\Request;

use GuzzleHttp\Exception\ConnectException;
use Nexus\Message\Sdk\Entity\Sandbox;
use Nexus\Message\Sdk\Entity\Token;
use Nexus\Message\Sdk\Config;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Nexus\Message\Sdk\Exceptions\ConnectionException;
use Nexus\Message\Sdk\Logger;
use Nexus\Message\Sdk\ValueObject\RequestData;

class HttpClient
{
    protected Token $token;
    protected string $baseUrl = '';
    protected array $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'Authorization' => 'Token '
    ];
    protected string $route = '';
    protected Sandbox $sandbox;
    protected $response;
    public function __construct(Token $token)
    {
        $this->token = $token;
        $this->sandbox = Sandbox::getInstance();
        $this->configure();
    }

    private function configure()
    {
        if ($this->token->validate()) {
            $this->headers['Authorization'] .= $this->sandbox->active() && is_object($this->sandbox->token()) ? $this->sandbox->token()->getToken() : $this->token->getToken();
        }

        $this->baseUrl = ($this->sandbox->active() ? Config::SANDBOX_BASE_URL : Config::BASE_URL) . '/' . Config::API_VERSION;
    }

    private function getRoute()
    {
        if ($this->route[0] === '/') {
            $this->route = substr($this->route, 1);
        }

        return $this->route;
    }

    public function getUrl()
    {
        return $this->baseUrl . '/' . $this->getRoute();
    }

    public function request(string $route, RequestData $request)
    {
        $this->route = trim($route);
        if (count($request->headers()) > 0) {
            $this->headers = array_merge($this->headers, $request->headers());
        }

        if ($this->headers['Content-Type'] === 'application/json') {
            $format = RequestOptions::JSON;
        }
        else if ($this->headers['Content-Type'] === 'application/x-www-form-urlencoded') {
            $format = RequestOptions::FORM_PARAMS;
        }
        else {
            $format = RequestOptions::QUERY;
        }

        try {
            $client = new Client([
                'http_errors' => false,
                'base_uri' => $this->baseUrl
            ]);

            $this->response = $client->request(
                $request->method(),
                $this->getUrl(),
                [
                    RequestOptions::HEADERS => $this->headers,
                    $format => $request->data(),
                    RequestOptions::CONNECT_TIMEOUT => Config::HTTP_CONNECT_TIMEOUT,
                    RequestOptions::TIMEOUT => Config::HTTP_TIMEOUT
                ]
            );

            if ($this->response->getStatusCode() !== 200) {
                throw new \Exception('Status code: ' . $this->response->getStatusCode() . ' Reason: ' . $this->response->getReasonPhrase());
            }
        } catch (ConnectException $e) {
            Logger::log('debug', $e->getMessage(), $e->getTrace());
        } catch (\Exception $e) {
            Logger::log('debug', $e->getMessage(), $e->getTrace());
        } finally {
            return $this->getResponse();
        }
    }

    public function getResponse(): array
    {
        if (!is_object($this->response) || !method_exists($this->response, 'getStatusCode')) {
            throw new ConnectionException($this->getUrl());
        }

        $response = [
            'code' => $this->response->getStatusCode(),
            'headers' => $this->response->getHeaders()
        ];

        if (isset($response['headers']['Content-Type'][0]) && $response['headers']['Content-Type'][0] === 'application/json') {
            $response['body'] = json_decode($this->response->getBody(), true);
        } else {
            $response['body'] = $this->response->getBody()->getContents();
        }

        return $response;
    }
}