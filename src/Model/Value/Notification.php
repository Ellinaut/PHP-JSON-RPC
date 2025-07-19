<?php

namespace Ellinaut\JsonRpc\Model\Value;

/**
 * @author Philipp Marien
 */
readonly class Notification extends Request
{
    public function __construct(string $method, ?array $params)
    {
        parent::__construct($method, $params, null);
    }
}
