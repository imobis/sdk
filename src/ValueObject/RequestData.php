<?php

namespace Imobis\Sdk\ValueObject;

class RequestData
{
    private array $data;
    private string $method;
    private array $headers;

    private function __construct(array $data, string $method = 'post', array $headers = [])
    {
        $this->data = $data;
        $this->method = strtoupper($method);
        $this->headers = $headers;
        $this->check();
    }

    public static function create(array $data = [], string $method = 'post', array $headers = []): RequestData
    {
        return new self($data, $method, $headers);
    }

    private function check()
    {
        if ($this->method !== 'POST' && $this->method !== 'GET') {
            $this->method = 'POST';
        }
    }

    public function data(): array
    {
        return $this->data;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function headers(): array
    {
        return $this->headers;
    }
}