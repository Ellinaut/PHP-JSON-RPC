<?php

namespace Ellinaut\JsonRpc\Tests;

use Ellinaut\JsonRpc\Exception\InvalidParamsException;
use Ellinaut\JsonRpc\Server\JsonRpcServer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * @author Philipp Marien
 */
class JsonRpcServerTest extends TestCase
{
    private ContainerInterface $container;
    private JsonRpcServer $server;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->server = new JsonRpcServer($this->container);
    }

    /**
     * Example from spec: rpc call with positional parameters
     */
    public function testRpcCallWithPositionalParameters(): void
    {
        $subtract = new RemoteProcedureMock(19);

        $this->container->expects($this->once())
            ->method('has')
            ->with('subtract')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('subtract')
            ->willReturn($subtract);

        $request = '{"jsonrpc": "2.0", "method": "subtract", "params": [42, 23], "id": 1}';
        $response = $this->server->handle($request);

        $this->assertJsonStringEqualsJsonString(
            '{"jsonrpc": "2.0", "result": 19, "id": 1}',
            $response
        );

        // Verify the procedure was called with correct params
        $this->assertTrue($subtract->wasCalledWith([42, 23], 1));
    }

    /**
     * Example from spec: rpc call with named parameters
     */
    public function testRpcCallWithNamedParameters(): void
    {
        $subtract = new RemoteProcedureMock(19);

        $this->container->expects($this->once())
            ->method('has')
            ->with('subtract')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('subtract')
            ->willReturn($subtract);

        $request = '{"jsonrpc": "2.0", "method": "subtract", "params": {"subtrahend": 23, "minuend": 42}, "id": 3}';
        $response = $this->server->handle($request);

        $this->assertJsonStringEqualsJsonString(
            '{"jsonrpc": "2.0", "result": 19, "id": 3}',
            $response
        );
    }

    /**
     * Example from spec: a Notification
     */
    public function testNotification(): void
    {
        $update = new RemoteProcedureMock(null);

        $this->container->expects($this->once())
            ->method('has')
            ->with('update')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('update')
            ->willReturn($update);

        $request = '{"jsonrpc": "2.0", "method": "update", "params": [1,2,3,4,5]}';
        $response = $this->server->handle($request);

        $this->assertNull($response);
        $this->assertTrue($update->wasCalledWith([1, 2, 3, 4, 5], null));
    }

    /**
     * Example from spec: rpc call of non-existent method
     */
    public function testNonExistentMethod(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('foobar')
            ->willReturn(false);

        $request = '{"jsonrpc": "2.0", "method": "foobar", "id": "1"}';
        $response = $this->server->handle($request);

        $expected = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32601,
                'message' => 'Invalid method: foobar',
                'data' => null
            ],
            'id' => '1'
        ];

        $this->assertEquals($expected, json_decode($response, true));
    }

    /**
     * Example from spec: rpc call with invalid JSON
     */
    public function testInvalidJson(): void
    {
        $request = '{"jsonrpc": "2.0", "method": "foobar, "params": "bar", "baz]';
        $response = $this->server->handle($request);

        $expected = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32700,
                'message' => 'Invalid json received by the server',
                'data' => null
            ],
            'id' => null
        ];

        $this->assertEquals($expected, json_decode($response, true));
    }

    /**
     * Example from spec: rpc call with invalid Request object
     */
    public function testInvalidRequest(): void
    {
        $request = '{"jsonrpc": "2.0", "method": 1, "params": "bar"}';
        $response = $this->server->handle($request);

        $result = json_decode($response, true);
        $this->assertEquals('2.0', $result['jsonrpc']);
        $this->assertEquals(-32600, $result['error']['code']);
        $this->assertStringContainsString('Method is required and must be a string', $result['error']['message']);
        $this->assertNull($result['id']);
    }

    /**
     * Example from spec: rpc call Batch, invalid JSON
     */
    public function testBatchInvalidJson(): void
    {
        $request = '[
            {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
            {"jsonrpc": "2.0", "method"
        ]';
        $response = $this->server->handle($request);

        $expected = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32700,
                'message' => 'Invalid json received by the server',
                'data' => null
            ],
            'id' => null
        ];

        $this->assertEquals($expected, json_decode($response, true));
    }

    /**
     * Example from spec: rpc call with an empty Array
     */
    public function testEmptyArray(): void
    {
        $request = '[]';
        $response = $this->server->handle($request);

        $this->assertEquals([
            'jsonrpc' => '2.0',
            'error' => ['code' => -32600, 'message' => 'Invalid request', 'data' => null],
            'id' => null
        ], json_decode($response, true));
    }

    /**
     * Example from spec: rpc call Batch
     */
    public function testBatchRequest(): void
    {
        // Setup mocks for different methods
        $sum = new RemoteProcedureMock(7);
        $notify = new RemoteProcedureMock(null);
        $subtract = new RemoteProcedureMock(19);
        $getData = new RemoteProcedureMock(['hello', 5]);

        $this->container->expects($this->exactly(5))
            ->method('has')
            ->willReturnMap([
                ['sum', true],
                ['notify_hello', true],
                ['subtract', true],
                ['foo.get', false],
                ['get_data', true]
            ]);

        $this->container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                ['sum', $sum],
                ['notify_hello', $notify],
                ['subtract', $subtract],
                ['get_data', $getData]
            ]);

        $request = '[
            {"jsonrpc": "2.0", "method": "sum", "params": [1,2,4], "id": "1"},
            {"jsonrpc": "2.0", "method": "notify_hello", "params": [7]},
            {"jsonrpc": "2.0", "method": "subtract", "params": [42,23], "id": "2"},
            {"foo": "boo"},
            {"jsonrpc": "2.0", "method": "foo.get", "params": {"name": "myself"}, "id": "5"},
            {"jsonrpc": "2.0", "method": "get_data", "id": "9"}
        ]';

        $response = $this->server->handle($request);
        $results = json_decode($response, true);

        // Should have 5 responses (notification excluded, but with one error response)
        $this->assertCount(5, $results);

        // Check individual responses
        $this->assertEquals(['jsonrpc' => '2.0', 'result' => 7, 'id' => '1'], $results[0]);
        $this->assertEquals(['jsonrpc' => '2.0', 'result' => 19, 'id' => '2'], $results[1]);
        $this->assertEquals(-32600, $results[2]['error']['code']); // Invalid Request
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'error' => ['code' => -32601, 'message' => 'Invalid method: foo.get', 'data' => null],
            'id' => '5'
        ], $results[3]);
    }

    /**
     * Example from spec: rpc call Batch (all notifications)
     */
    public function testBatchAllNotifications(): void
    {
        $notify = new RemoteProcedureMock(null);

        $this->container->expects($this->exactly(2))
            ->method('has')
            ->with('notify_sum')
            ->willReturn(true);

        $this->container->expects($this->exactly(2))
            ->method('get')
            ->with('notify_sum')
            ->willReturn($notify);

        $request = '[
            {"jsonrpc": "2.0", "method": "notify_sum", "params": [1,2,4]},
            {"jsonrpc": "2.0", "method": "notify_sum", "params": [7]}
        ]';

        $response = $this->server->handle($request);

        $this->assertNull($response);
        $this->assertEquals(2, $notify->getCallCount());
    }

    /**
     * Test when container returns non-RemoteProcedure
     */
    public function testInvalidProcedureType(): void
    {
        $this->container->expects($this->once())
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn(new \stdClass()); // Wrong type

        $request = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $response = $this->server->handle($request);

        $result = json_decode($response, true);
        $this->assertEquals(-32601, $result['error']['code']);
        $this->assertEquals('Invalid method: test', $result['error']['message']);
    }

    /**
     * Test when procedure throws JsonRcpException
     */
    public function testProcedureThrowsJsonRcpException(): void
    {
        $procedure = new RemoteProcedureMock(null);
        $procedure->setException(new InvalidParamsException('Missing required parameter', ['param' => 'id']));

        $this->container->expects($this->once())
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn($procedure);

        $request = '{"jsonrpc": "2.0", "method": "test", "params": [], "id": 1}';
        $response = $this->server->handle($request);

        $result = json_decode($response, true);
        $this->assertEquals(-32602, $result['error']['code']);
        $this->assertEquals('Missing required parameter', $result['error']['message']);
        $this->assertEquals(['param' => 'id'], $result['error']['data']);
        $this->assertEquals(1, $result['id']);
    }

    /**
     * Test when procedure throws generic exception
     */
    public function testProcedureThrowsGenericException(): void
    {
        $procedure = new RemoteProcedureMock(null);
        $procedure->setException(new \RuntimeException('Something went wrong', 500));

        $this->container->expects($this->once())
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn($procedure);

        $request = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $response = $this->server->handle($request);

        $result = json_decode($response, true);
        $this->assertEquals(500, $result['error']['code']);
        $this->assertEquals('Something went wrong', $result['error']['message']);
        $this->assertEquals(1, $result['id']);
    }

    /**
     * Test successful request with null result
     */
    public function testSuccessfulRequestWithNullResult(): void
    {
        $procedure = new RemoteProcedureMock(null);

        $this->container->expects($this->once())
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('test')
            ->willReturn($procedure);

        $request = '{"jsonrpc": "2.0", "method": "test", "id": 1}';
        $response = $this->server->handle($request);

        $this->assertJsonStringEqualsJsonString(
            '{"jsonrpc": "2.0", "result": null, "id": 1}',
            $response
        );
    }
}