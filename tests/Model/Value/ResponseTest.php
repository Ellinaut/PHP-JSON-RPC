<?php

namespace Ellinaut\JsonRpc\Tests\Model\Value;

use Ellinaut\JsonRpc\Exception\JsonRcpException;
use Ellinaut\JsonRpc\Model\Value\Error;
use Ellinaut\JsonRpc\Model\Value\Response;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class ResponseTest extends TestCase
{
    public function testConstructor(): void
    {
        // Test with result data and id
        $response1 = new Response('success', 123);
        $this->assertEquals('success', $response1->data);
        $this->assertEquals(123, $response1->id);

        // Test with array result
        $response2 = new Response(['data' => 'value'], 'abc');
        $this->assertEquals(['data' => 'value'], $response2->data);
        $this->assertEquals('abc', $response2->id);

        // Test with Error object and id
        $error = new Error(-32600, 'Invalid Request', null);
        $response3 = new Response($error, 456);
        $this->assertInstanceOf(Error::class, $response3->data);
        $this->assertEquals(-32600, $response3->data->code);
        $this->assertEquals(456, $response3->id);

        // Test with null id
        $response4 = new Response('result', null);
        $this->assertEquals('result', $response4->data);
        $this->assertNull($response4->id);

        // Test with float id
        $response5 = new Response('result', 3.14);
        $this->assertEquals(3.14, $response5->id);
    }

    public function testFromArrayWithValidResult(): void
    {
        // Test with string result
        $data1 = [
            'jsonrpc' => '2.0',
            'result' => 'success',
            'id' => 1
        ];
        $response1 = Response::fromArray($data1);
        $this->assertEquals('success', $response1->data);
        $this->assertEquals(1, $response1->id);

        // Test with array result
        $data2 = [
            'jsonrpc' => '2.0',
            'result' => ['key' => 'value', 'number' => 42],
            'id' => 'request-123'
        ];
        $response2 = Response::fromArray($data2);
        $this->assertEquals(['key' => 'value', 'number' => 42], $response2->data);
        $this->assertEquals('request-123', $response2->id);

        // Test with null result
        $data3 = [
            'jsonrpc' => '2.0',
            'result' => null,
            'id' => 2
        ];
        $response3 = Response::fromArray($data3);
        $this->assertNull($response3->data);
        $this->assertEquals(2, $response3->id);

        // Test with different id types
        $dataFloat = ['jsonrpc' => '2.0', 'result' => 'ok', 'id' => 3.14];
        $responseFloat = Response::fromArray($dataFloat);
        $this->assertEquals(3.14, $responseFloat->id);

        $dataNull = ['jsonrpc' => '2.0', 'result' => 'ok', 'id' => null];
        $responseNull = Response::fromArray($dataNull);
        $this->assertNull($responseNull->id);
    }

    public function testFromArrayWithValidError(): void
    {
        // Test with complete error object
        $data1 = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32601,
                'message' => 'Method not found',
                'data' => ['method' => 'unknownMethod']
            ],
            'id' => 1
        ];
        $response1 = Response::fromArray($data1);
        $this->assertInstanceOf(Error::class, $response1->data);
        $this->assertEquals(-32601, $response1->data->code);
        $this->assertEquals('Method not found', $response1->data->message);
        $this->assertEquals(['method' => 'unknownMethod'], $response1->data->data);

        // Test with minimal error object
        $data2 = [
            'jsonrpc' => '2.0',
            'error' => [
                'code' => -32602,
                'message' => 'Invalid params'
            ],
            'id' => 2
        ];
        $response2 = Response::fromArray($data2);
        $this->assertInstanceOf(Error::class, $response2->data);
        $this->assertEquals(-32602, $response2->data->code);
        $this->assertEquals('Invalid params', $response2->data->message);
        $this->assertNull($response2->data->data);

        // Test error defaults when fields are missing
        $data3 = [
            'jsonrpc' => '2.0',
            'error' => [],
            'id' => 3
        ];
        $response3 = Response::fromArray($data3);
        $this->assertInstanceOf(Error::class, $response3->data);
        $this->assertEquals(-32500, $response3->data->code); // Default code
        $this->assertEquals('Unknown error', $response3->data->message); // Default message
        $this->assertNull($response3->data->data);
    }

    public function testFromArrayWithInvalidJsonRpcVersion(): void
    {
        // Missing jsonrpc field
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "jsonrpc" must be "2.0"');
        $this->expectExceptionCode(-32501);
        Response::fromArray(['result' => 'test', 'id' => 1]);
    }

    public function testFromArrayWithWrongJsonRpcVersion(): void
    {
        // Wrong jsonrpc version
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "jsonrpc" must be "2.0"');
        $this->expectExceptionCode(-32501);
        Response::fromArray(['jsonrpc' => '1.0', 'result' => 'test', 'id' => 1]);
    }

    public function testFromArrayWithMissingResultAndError(): void
    {
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "result" or "error" must be present');
        $this->expectExceptionCode(-32502);
        Response::fromArray(['jsonrpc' => '2.0', 'id' => 1]);
    }

    public function testFromArrayWithMissingId(): void
    {
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "id" must be present and be a string, integer, float, or null');
        $this->expectExceptionCode(-32503);
        Response::fromArray(['jsonrpc' => '2.0', 'result' => 'test']);
    }

    public function testFromArrayWithInvalidIdArray(): void
    {
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "id" must be present and be a string, integer, float, or null');
        $this->expectExceptionCode(-32503);
        Response::fromArray(['jsonrpc' => '2.0', 'result' => 'test', 'id' => ['invalid']]);
    }

    public function testFromArrayWithInvalidIdBoolean(): void
    {
        $this->expectException(JsonRcpException::class);
        $this->expectExceptionMessage('Invalid response: "id" must be present and be a string, integer, float, or null');
        $this->expectExceptionCode(-32503);
        Response::fromArray(['jsonrpc' => '2.0', 'result' => 'test', 'id' => true]);
    }

    public function testJsonSerializeWithResult(): void
    {
        // Test with string result
        $response1 = new Response('success', 123);
        $json1 = $response1->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'result' => 'success',
            'id' => 123
        ], $json1);

        // Test with array result
        $response2 = new Response(['data' => 'value'], 'abc');
        $json2 = $response2->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'result' => ['data' => 'value'],
            'id' => 'abc'
        ], $json2);

        // Test with null result
        $response3 = new Response(null, 1);
        $json3 = $response3->jsonSerialize();
        $this->assertEquals([
            'jsonrpc' => '2.0',
            'result' => null,
            'id' => 1
        ], $json3);
    }

    public function testJsonSerializeWithError(): void
    {
        $error = new Error(-32600, 'Invalid Request', ['details' => 'Missing field']);
        $response = new Response($error, 456);
        $json = $response->jsonSerialize();

        $this->assertEquals('2.0', $json['jsonrpc']);
        $this->assertArrayHasKey('error', $json);
        $this->assertSame($error, $json['error']);
        $this->assertEquals(456, $json['id']);
        $this->assertArrayNotHasKey('result', $json);
    }
}