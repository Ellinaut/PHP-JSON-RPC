<?php

namespace Ellinaut\JsonRpc\Model\Value;

use Ellinaut\JsonRpc\Exception\JsonRcpException;
use JsonSerializable;

/**
 * @author Philipp Marien
 */
readonly class Response implements JsonSerializable
{
    public function __construct(
        public mixed $data,
        public string|int|float|null $id,
    ) {
    }

    public static function fromArray(array $data): static
    {
        if (!array_key_exists('jsonrpc', $data) || $data['jsonrpc'] !== '2.0') {
            throw new  JsonRcpException(
                'Invalid response: "jsonrpc" must be "2.0"',
                -32501
            );
        }

        if (!array_key_exists('result', $data) && !array_key_exists('error', $data)) {
            throw new  JsonRcpException(
                'Invalid response: "result" or "error" must be present',
                -32502
            );
        }

        if (
            !array_key_exists('id', $data) ||
            (!is_string($data['id']) && !is_int($data['id']) && !is_float($data['id']) && !is_null($data['id']))
        ) {
            throw new JsonRcpException(
                'Invalid response: "id" must be present and be a string, integer, float, or null',
                -32503
            );
        }

        $error = null;
        if (array_key_exists('error', $data)) {
            $error = new Error(
                $data['error']['code'] ?? -32500,
                $data['error']['message'] ?? 'Unknown error',
                $data['error']['data'] ?? null
            );
        }

        return new static(
            data: $error ?? $data['result'],
            id: $data['id']
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'jsonrpc' => '2.0',
        ];

        if ($this->data instanceof Error) {
            $data['error'] = $this->data;
        } else {
            $data['result'] = $this->data;
        }

        $data['id'] = $this->id;

        return $data;
    }
}
