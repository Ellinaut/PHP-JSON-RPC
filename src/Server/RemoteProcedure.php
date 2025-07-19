<?php

namespace Ellinaut\JsonRpc\Server;

use Ellinaut\JsonRpc\Model\Value\Request;

/**
 * @author Philipp Marien
 */
interface RemoteProcedure
{
    /**
     * Validates the parameters for the remote procedure call.
     *
     * @throws \Ellinaut\JsonRpc\Exception\InvalidParamsException
     */
    public function validate(array $params): void;

    /**
     * @param array $params An array containing the parameters required for execution.
     * @param string|int|float|null $id An identifier used to determine the context from the rpc request
     * @return mixed The result of the execution process. Must be serializable with json_encode.
     */
    public function execute(array $params, string|int|float|null $id): mixed;
}
