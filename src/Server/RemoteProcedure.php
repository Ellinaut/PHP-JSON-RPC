<?php

namespace Ellinaut\JsonRpc\Server;

/**
 * @author Philipp Marien
 */
interface RemoteProcedure
{
    /**
     * @param array $params An array containing the parameters required for execution.
     * @param string|int|float|null $id An identifier used to determine the context from the rpc request
     * @return mixed The result of the execution process. Must be serializable with json_encode.
     */
    public function execute(array $params, string|int|float|null $id): mixed;
}
