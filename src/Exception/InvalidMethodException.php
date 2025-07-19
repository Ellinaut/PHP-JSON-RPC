<?php

namespace Ellinaut\JsonRpc\Exception;

/**
 * @author Philipp Marien
 */
class InvalidMethodException extends JsonRcpException
{
    public function __construct(string $message = 'The requested method does not exist', ?array $data = null)
    {
        parent::__construct($message, -32601, $data);
    }
}
