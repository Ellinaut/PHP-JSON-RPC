<?php

namespace Ellinaut\JsonRpc\Client;

use Ellinaut\JsonRpc\Exception\JsonRcpException;
use Ellinaut\JsonRpc\Model\Value\Request;
use Ellinaut\JsonRpc\Model\Value\Response;
use Generator;
use Throwable;

/**
 * @author Philipp Marien
 */
readonly class JsonRpcClient
{
    public function __construct(private TransportInterface $transport)
    {
    }

    /**
     * @param Request $request
     * @return Response|null
     */
    public function send(Request $request): ?Response
    {
        try {
            $responseData = $this->transport->send(
                json_encode($request, JSON_THROW_ON_ERROR)
            );

            if (!$responseData) {
                return null;
            }

            return Response::fromArray(
                json_decode($responseData, true, 512, JSON_THROW_ON_ERROR)
            );
        } catch (JsonRcpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new JsonRcpException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                data: null
            );
        }
    }

    /**
     * @param Request[] $requests
     * @return Generator<Response>
     */
    public function sendBatch(array $requests): Generator
    {
        try {
            $rawResponse = $this->transport->send(
                json_encode($requests, JSON_THROW_ON_ERROR)
            );
            if (!$rawResponse) {
                return;
            }

            $response = json_decode($rawResponse, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($response)) {
                return;
            }

            foreach ($response as $item) {
                yield Response::fromArray($item);
            }
        } catch (JsonRcpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new JsonRcpException(
                message: $exception->getMessage(),
                code: $exception->getCode(),
                data: null
            );
        }
    }
}
