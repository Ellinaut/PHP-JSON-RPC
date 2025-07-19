<?php

namespace Ellinaut\JsonRpc\Server;

use Ellinaut\JsonRpc\Exception\InvalidJsonException;
use Ellinaut\JsonRpc\Exception\InvalidMethodException;
use Ellinaut\JsonRpc\Exception\InvalidRequestException;
use Ellinaut\JsonRpc\Exception\JsonRcpException;
use Ellinaut\JsonRpc\Model\Value\Error;
use Ellinaut\JsonRpc\Model\Value\Request;
use Ellinaut\JsonRpc\Model\Value\Response;
use JsonException;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @author Philipp Marien
 */
readonly class JsonRpcServer
{
    public function __construct(private ContainerInterface $procedureRegister)
    {
    }

    /**
     * @throws JsonException
     */
    public function handle(string $json): ?string
    {
        try {
            try {
                $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new InvalidJsonException();
            }

            if (!is_array($data) || count($data) === 0) {
                throw new InvalidRequestException('Invalid request');
            }

            if (isset($data[0])) {
                $response = [];
                foreach ($data as $item) {
                    $result = $this->executeProcedure($item);
                    if ($result) {
                        $response[] = $result;
                    }
                }
                if (count($response) === 0) {
                    $response = null;
                }
            } else {
                $response = $this->executeProcedure($data);
            }
        } catch (JsonRcpException $exception) {
            $response = $this->createErrorResponse($exception);
        }

        return $response !== null ? json_encode($response, JSON_THROW_ON_ERROR) : null;
    }

    protected function executeProcedure(array $data): ?Response
    {
        $id = null;
        try {
            $request = Request::fromArray($data);
            $id = $request->id;

            if (!$this->procedureRegister->has($request->method)) {
                throw new InvalidMethodException('Invalid method: ' . $request->method);
            }

            $procedure = $this->procedureRegister->get($request->method);
            if (!$procedure instanceof RemoteProcedure) {
                throw new InvalidMethodException('Invalid method: ' . $request->method);
            }

            $result = $procedure->execute($request->params ?? [], $request->id);

            if (!$request->id) {
                return null; // No response for notifications
            }

            return new Response($result, $request->id);
        } catch (JsonRcpException $exception) {
            return $this->createErrorResponse($exception, $id ?? $data['id'] ?? null);
        } catch (Throwable $exception) {
            return $this->createErrorResponse(
                new JsonRcpException($exception->getMessage(), $exception->getCode()),
                $id ?? $data['id'] ?? null
            );
        }
    }

    protected function createErrorResponse(JsonRcpException $exception, string|float|int|null $id = null): Response
    {
        return new Response(
            new Error(
                code: $exception->getCode(),
                message: $exception->getMessage(),
                data: $exception->data
            ),
            $id
        );
    }
}
