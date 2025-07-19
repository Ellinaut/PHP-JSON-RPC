<?php

namespace Ellinaut\JsonRpc\Tests\Model\Value;

use Ellinaut\JsonRpc\Exception\InvalidRequestException;
use Ellinaut\JsonRpc\Model\Value\Request;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class RequestTest extends TestCase
{
    public function testConstructor(): void
    {
        // Test with all parameters
        $request1 = new Request('testMethod', ['param1' => 'value1'], 123);
        $this->assertEquals('testMethod', $request1->method);
        $this->assertEquals(['param1' => 'value1'], $request1->params);
        $this->assertEquals(123, $request1->id);

        // Test notification (no id)
        $request2 = new Request('notify', ['data' => 'test'], null);
        $this->assertEquals('notify', $request2->method);
        $this->assertEquals(['data' => 'test'], $request2->params);
        $this->assertNull($request2->id);

        // Test with method only
        $request3 = new Request('simpleMethod', null, null);
        $this->assertEquals('simpleMethod', $request3->method);
        $this->assertNull($request3->params);
        $this->assertNull($request3->id);
    }

    public function testFromArrayWithValidData(): void
    {
        // Test with all fields
        $data1 = [
            'jsonrpc' => '2.0',
            'method' => 'testMethod',
            'params' => ['param1' => 'value1', 'param2' => 'value2'],
            'id' => 1
        ];
        $request1 = Request::fromArray($data1);
        $this->assertEquals('testMethod', $request1->method);
        $this->assertEquals(['param1' => 'value1', 'param2' => 'value2'], $request1->params);
        $this->assertEquals(1, $request1->id);

        // Test without params
        $data2 = [
            'jsonrpc' => '2.0',
            'method' => 'getInfo',
            'id' => 'abc123'
        ];
        $request2 = Request::fromArray($data2);
        $this->assertEquals('getInfo', $request2->method);
        $this->assertNull($request2->params);
        $this->assertEquals('abc123', $request2->id);

        // Test notification (without id)
        $data3 = [
            'jsonrpc' => '2.0',
            'method' => 'notify',
            'params' => ['message' => 'Hello']
        ];
        $request3 = Request::fromArray($data3);
        $this->assertEquals('notify', $request3->method);
        $this->assertEquals(['message' => 'Hello'], $request3->params);
        $this->assertNull($request3->id);

        // Test different id types
        $dataFloat = ['jsonrpc' => '2.0', 'method' => 'test', 'id' => 3.14];
        $requestFloat = Request::fromArray($dataFloat);
        $this->assertEquals(3.14, $requestFloat->id);
    }

    public function testFromArrayWithInvalidJsonRpcVersion(): void
    {
        // Missing jsonrpc field
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');
        Request::fromArray(['method' => 'test', 'id' => 1]);
    }

    public function testFromArrayWithWrongJsonRpcVersion(): void
    {
        // Wrong jsonrpc version
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Invalid JSON-RPC version');
        Request::fromArray(['jsonrpc' => '1.0', 'method' => 'test', 'id' => 1]);
    }

    public function testFromArrayWithMissingMethod(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Method is required and must be a string');
        Request::fromArray(['jsonrpc' => '2.0', 'id' => 1]);
    }

    public function testFromArrayWithInvalidMethodType(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Method is required and must be a string');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 123, 'id' => 1]);
    }

    public function testFromArrayWithInvalidParams(): void
    {
        // String params
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Params must be a structured value or omitted');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 'test', 'params' => 'invalid']);
    }

    public function testFromArrayWithInvalidParamsNumber(): void
    {
        // Number params
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Params must be a structured value or omitted');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 'test', 'params' => 123]);
    }

    public function testFromArrayWithInvalidParamsBoolean(): void
    {
        // Boolean params
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Params must be a structured value or omitted');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 'test', 'params' => true]);
    }

    public function testFromArrayWithInvalidIdArray(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Id must be a string, integer, float, or omitted');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 'test', 'id' => ['invalid']]);
    }

    public function testFromArrayWithInvalidIdBoolean(): void
    {
        $this->expectException(InvalidRequestException::class);
        $this->expectExceptionMessage('Id must be a string, integer, float, or omitted');
        Request::fromArray(['jsonrpc' => '2.0', 'method' => 'test', 'id' => true]);
    }

    public function testJsonSerialize(): void
    {
        // Test with all fields
        $request1 = new Request('testMethod', ['param' => 'value'], 1);
        $json1 = $request1->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'method' => 'testMethod',
            'params' => ['param' => 'value'],
            'id' => 1
        ], $json1);

        // Test without params
        $request2 = new Request('testMethod', null, 'abc');
        $json2 = $request2->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'method' => 'testMethod',
            'id' => 'abc'
        ], $json2);
        $this->assertArrayNotHasKey('params', $json2);

        // Test without id (notification)
        $request3 = new Request('notify', ['data' => 'test'], null);
        $json3 = $request3->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'method' => 'notify',
            'params' => ['data' => 'test']
        ], $json3);
        $this->assertArrayNotHasKey('id', $json3);

        // Verify jsonrpc is always included
        $request4 = new Request('test', null, null);
        $json4 = $request4->jsonSerialize();
        $this->assertArrayHasKey('jsonrpc', $json4);
        $this->assertEquals('2.0', $json4['jsonrpc']);
    }
}