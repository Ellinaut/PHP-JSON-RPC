<?php

namespace Ellinaut\JsonRpc\Exception;

/**
 * @author Philipp Marien
 */
class InvalidRequestException extends JsonRcpException
{
    public function __construct(
        string $message = 'Invalid request object',
        ?array $data = null
    ) {
        parent::__construct($message, -32600, $data);
    }
}

