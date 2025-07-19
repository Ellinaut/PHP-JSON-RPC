<?php

namespace Ellinaut\JsonRpc\Model\Value;

use Ellinaut\JsonRpc\Exception\InvalidRequestException;
use JsonSerializable;

/**
 * @author Philipp Marien
 */
readonly class Request implements JsonSerializable
{
    public function __construct(
        public string $method,
        public ?array $params,
        public string|int|float|null $id
    ) {

    }

    public static function fromArray(array $data): static
    {
        if (!array_key_exists('jsonrpc', $data) || $data['jsonrpc'] !== '2.0') {
            throw new InvalidRequestException('Invalid JSON-RPC version');
        }

        if (!array_key_exists('method', $data) || !is_string($data['method'])) {
            throw new InvalidRequestException('Method is required and must be a string');
        }

        if (array_key_exists('params', $data) && !is_array($data['params'])) {
            throw new InvalidRequestException('Params must be a structured value or omitted');
        }

        if (
            array_key_exists('id', $data)
            && (!is_string($data['id']) && !is_int($data['id']) && !is_float($data['id']))
        ) {
            throw new InvalidRequestException('Id must be a string, integer, float, or omitted');
        }

        return new static($data['method'], $data['params'] ?? null, $data['id'] ?? null);
    }

    public function jsonSerialize(): array
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => $this->method,
        ];

        if ($this->params) {
            $data['params'] = $this->params;
        }

        if ($this->id) {
            $data['id'] = $this->id;
        }

        return $data;
    }
}
