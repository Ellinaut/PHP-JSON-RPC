<?php

namespace Ellinaut\JsonRpc\Model\Value;

/**
 * @author Philipp Marien
 */
readonly class ProcedureCall extends Request
{
    public function __construct(string $method, ?array $params, float|int|string $id)
    {
        parent::__construct($method, $params, $id);
    }
}
