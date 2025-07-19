<?php

namespace Ellinaut\JsonRpc\Exception;

/**
 * @author Philipp Marien
 */
class InvalidJsonException extends JsonRcpException
{
    public function __construct(
        string $message = 'Invalid json received by the server',
        ?array $data = null
    ) {
        parent::__construct($message, -32700, $data);
    }
}

