<?php

namespace Ellinaut\JsonRpc\Tests;

use Ellinaut\JsonRpc\Client\JsonRpcClient;
use Ellinaut\JsonRpc\Exception\JsonRcpException;
use Ellinaut\JsonRpc\Model\Value\Error;
use Ellinaut\JsonRpc\Model\Value\Request;
use Ellinaut\JsonRpc\Model\Value\Response;
use Ellinaut\JsonRpc\Tests\TransportMock;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class JsonRpcClientTest extends TestCase
{
    private TransportMock $transport;
    private JsonRpcClient $client;

    protected function setUp(): void
    {
        $this->transport = new TransportMock();
        $this->client = new JsonRpcClient($this->transport);
    }

    public function testSendSuccessfulRequest(): void
    {
        $request = new Request('test.method', ['param1' => 'value1'], 1);
        $responseJson = '{"jsonrpc":"2.0","result":"success","id":1}';

        $this->transport->addResponse($responseJson);

        $response = $this->client->send($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('success', $response->data);
        $this->assertEquals(1, $response->id);

        $sentRequest = $this->transport->getLastSentRequest();
        $decodedRequest = json_decode($sentRequest, true);
        $this->assertEquals('2.0', $decodedRequest['jsonrpc']);
        $this->assertEquals('test.method', $decodedRequest['method']);
        $this->assertEquals(['param1' => 'value1'], $decodedRequest['params']);
        $this->assertEquals(1, $decodedRequest['id']);
    }

    public function testSendNotificationReturnsNull(): void
    {
        $request = new Request('test.notification', ['param1' => 'value1'], null);

        $this->transport->addResponse(null);

        $response = $this->client->send($request);

        $this->assertNull($response);

        $sentRequest = $this->transport->getLastSentRequest();
        $decodedRequest = json_decode($sentRequest, true);
        $this->assertEquals('2.0', $decodedRequest['jsonrpc']);
        $this->assertEquals('test.notification', $decodedRequest['method']);
        $this->assertArrayNotHasKey('id', $decodedRequest);
    }

    public function testSendErrorResponse(): void
    {
        $request = new Request('test.error', [], 1);
        $responseJson = '{"jsonrpc":"2.0","error":{"code":-32601,"message":"Method not found"},"id":1}';

        $this->transport->addResponse($responseJson);

        $response = $this->client->send($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(Error::class, $response->data);
        $this->assertEquals(-32601, $response->data->code);
        $this->assertEquals('Method not found', $response->data->message);
        $this->assertEquals(1, $response->id);
    }

    public function testSendBatchRequests(): void
    {
        $requests = [
            new Request('method1', ['a' => 1], 1),
            new Request('method2', ['b' => 2], 2),
            new Request('notification', ['c' => 3], null)
        ];

        // JsonRpcClient now properly handles batch responses as Generator
        $responsesJson = '[{"jsonrpc":"2.0","result":"result1","id":1},{"jsonrpc":"2.0","result":"result2","id":2}]';

        $this->transport->addResponse($responsesJson);

        $responses = $this->client->sendBatch($requests);

        // sendBatch now returns a Generator
        $this->assertInstanceOf(\Generator::class, $responses);

        // Convert generator to array for testing
        $responseArray = iterator_to_array($responses);
        $this->assertCount(2, $responseArray);

        $this->assertInstanceOf(Response::class, $responseArray[0]);
        $this->assertEquals('result1', $responseArray[0]->data);
        $this->assertEquals(1, $responseArray[0]->id);

        $this->assertInstanceOf(Response::class, $responseArray[1]);
        $this->assertEquals('result2', $responseArray[1]->data);
        $this->assertEquals(2, $responseArray[1]->id);

        // Verify the request was sent correctly
        $sentRequest = $this->transport->getLastSentRequest();
        $decodedRequest = json_decode($sentRequest, true);
        $this->assertIsArray($decodedRequest);
        $this->assertCount(3, $decodedRequest);

        // Verify structure of sent batch request
        $this->assertEquals('method1', $decodedRequest[0]['method']);
        $this->assertEquals(['a' => 1], $decodedRequest[0]['params']);
        $this->assertEquals(1, $decodedRequest[0]['id']);

        $this->assertEquals('method2', $decodedRequest[1]['method']);
        $this->assertEquals(['b' => 2], $decodedRequest[1]['params']);
        $this->assertEquals(2, $decodedRequest[1]['id']);

        $this->assertEquals('notification', $decodedRequest[2]['method']);
        $this->assertEquals(['c' => 3], $decodedRequest[2]['params']);
        $this->assertArrayNotHasKey('id', $decodedRequest[2]);
    }

    public function testSendBatchReturnsEmptyGeneratorForNullResponse(): void
    {
        $requests = [new Request('notification', [], null)];

        // Transport returns null (no response for notifications)
        $this->transport->addResponse(null);

        $responses = $this->client->sendBatch($requests);

        // sendBatch returns a Generator, but it should be empty for null transport response
        $this->assertInstanceOf(\Generator::class, $responses);

        // Convert to array to check if empty
        $responseArray = iterator_to_array($responses);
        $this->assertEmpty($responseArray);
    }

    public function testSendBatchReturnsEmptyGeneratorForNonArrayResponse(): void
    {
        $requests = [new Request('test.method', [], 1)];

        // Transport returns non-array JSON - a string that is not a valid JSON array
        $this->transport->addResponse('"just_a_string"');

        $responses = $this->client->sendBatch($requests);

        // sendBatch returns a Generator, but it should be empty for non-array response
        $this->assertInstanceOf(\Generator::class, $responses);

        // Convert to array to check if empty
        $responseArray = iterator_to_array($responses);
        $this->assertEmpty($responseArray);
    }

    public function testSendThrowsJsonRpcExceptionForInvalidResponse(): void
    {
        $request = new Request('test.method', [], 1);
        // Invalid response missing required fields
        $responseJson = '{"invalid":"response"}';

        $this->transport->addResponse($responseJson);

        $this->expectException(JsonRcpException::class);

        $this->client->send($request);
    }

    public function testSendWrapsGenericExceptions(): void
    {
        $request = new Request('test.method', [], 1);

        // Create a client with a transport that will throw an exception
        $failingTransport = new class implements \Ellinaut\JsonRpc\Client\TransportInterface {
            public function send(string $json): ?string
            {
                throw new JsonException('Invalid JSON');
            }
        };

        $client = new JsonRpcClient($failingTransport);

        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid JSON');

        $client->send($request);
    }

    public function testSendBatchWrapsGenericExceptions(): void
    {
        $requests = [new Request('test.method', [], 1)];

        // Create a client with a transport that will throw an exception
        $failingTransport = new class implements \Ellinaut\JsonRpc\Client\TransportInterface {
            public function send(string $json): ?string
            {
                throw new JsonException('Invalid JSON');
            }
        };

        $client = new JsonRpcClient($failingTransport);

        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid JSON');

        // Exception should be thrown when generator is consumed
        $responses = $client->sendBatch($requests);
        iterator_to_array($responses); // This will trigger the exception
    }

    public function testSendBatchWrapsJsonRpcExceptions(): void
    {
        $requests = [new Request('test.method', [], 1)];

        // Transport returns invalid response that will cause Response::fromArray to throw
        $this->transport->addResponse('[{"invalid":"response"}]');

        $this->expectException(JsonRcpException::class);

        // Exception should be thrown when generator is consumed
        $responses = $this->client->sendBatch($requests);
        iterator_to_array($responses); // This will trigger the exception
    }
}