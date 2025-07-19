<?php

namespace Ellinaut\JsonRpc\Exception;


/**
 * @author Philipp Marien
 */
class JsonRcpException extends \RuntimeException
{
    public function __construct(string $message, int $code, public ?array $data = null)
    {
        parent::__construct($message, $code);
    }
}
