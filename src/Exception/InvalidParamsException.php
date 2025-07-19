<?php

namespace Ellinaut\JsonRpc\Exception;

/**
 * @author Philipp Marien
 */
class InvalidParamsException extends JsonRcpException
{
    public function __construct(string $message = 'Invalid method parameter(s)', ?array $data = null)
    {
        parent::__construct($message, -32602, $data);
    }
}
