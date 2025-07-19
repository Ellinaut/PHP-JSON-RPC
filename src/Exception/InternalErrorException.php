<?php

namespace Ellinaut\JsonRpc\Exception;

use JetBrains\PhpStorm\Pure;

/**
 * @author Philipp Marien
 */
class InternalErrorException extends JsonRcpException
{
    #[Pure]
    public function __construct(string $message = 'An internal error occurred', ?array $data = null)
    {
        parent::__construct($message, -32603, $data);
    }
}
