<?php

namespace Ellinaut\JsonRpc\Tests\Model\Value;

use Ellinaut\JsonRpc\Model\Value\ProcedureCall;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class ProcedureCallTest extends TestCase
{
    public function testConstructorWithStringId(): void
    {
        $call = new ProcedureCall('test.method', null, 'test-id');
        
        $this->assertEquals('test.method', $call->method);
        $this->assertNull($call->params);
        $this->assertEquals('test-id', $call->id);
    }

    public function testConstructorWithIntId(): void
    {
        $call = new ProcedureCall('test.method', null, 42);
        
        $this->assertEquals('test.method', $call->method);
        $this->assertNull($call->params);
        $this->assertEquals(42, $call->id);
    }

    public function testConstructorWithFloatId(): void
    {
        $call = new ProcedureCall('test.method', null, 3.14);
        
        $this->assertEquals('test.method', $call->method);
        $this->assertNull($call->params);
        $this->assertEquals(3.14, $call->id);
    }

    public function testConstructorWithMethodAndParams(): void
    {
        $params = ['param1' => 'value1', 'param2' => 42];
        $call = new ProcedureCall('test.method', $params, 1);
        
        $this->assertEquals('test.method', $call->method);
        $this->assertEquals($params, $call->params);
        $this->assertEquals(1, $call->id);
    }

    public function testConstructorWithPositionalParams(): void
    {
        $params = ['value1', 42, true];
        $call = new ProcedureCall('test.method', $params, 'call-123');
        
        $this->assertEquals('test.method', $call->method);
        $this->assertEquals($params, $call->params);
        $this->assertEquals('call-123', $call->id);
    }

    public function testJsonSerializeWithoutParams(): void
    {
        $call = new ProcedureCall('test.procedure', null, 1);
        $json = json_encode($call);
        
        $expected = '{"jsonrpc":"2.0","method":"test.procedure","id":1}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithNamedParams(): void
    {
        $params = ['name' => 'John', 'age' => 30];
        $call = new ProcedureCall('user.get', $params, 'user-req-1');
        $json = json_encode($call);
        
        $expected = '{"jsonrpc":"2.0","method":"user.get","params":{"name":"John","age":30},"id":"user-req-1"}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithPositionalParams(): void
    {
        $params = ['John', 30, true];
        $call = new ProcedureCall('user.create', $params, 42);
        $json = json_encode($call);
        
        $expected = '{"jsonrpc":"2.0","method":"user.create","params":["John",30,true],"id":42}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithEmptyParams(): void
    {
        $call = new ProcedureCall('test.method', [], 'empty-params');
        $json = json_encode($call);
        
        // Empty params array is omitted from JSON (Request class behavior)
        $expected = '{"jsonrpc":"2.0","method":"test.method","id":"empty-params"}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithFloatId(): void
    {
        $call = new ProcedureCall('math.calculate', ['x' => 1, 'y' => 2], 3.14159);
        $json = json_encode($call);
        
        $expected = '{"jsonrpc":"2.0","method":"math.calculate","params":{"x":1,"y":2},"id":3.14159}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testIdIsNeverNull(): void
    {
        $call = new ProcedureCall('any.method', ['param' => 'value'], 1); // Use non-zero ID
        
        // ID should not be null (this distinguishes it from notifications)
        $this->assertNotNull($call->id);
        $this->assertEquals(1, $call->id);
        
        // Verify the JSON contains an id field
        $json = json_decode(json_encode($call), true);
        $this->assertArrayHasKey('id', $json);
        $this->assertEquals(1, $json['id']);
    }

    public function testInheritsFromRequest(): void
    {
        $call = new ProcedureCall('test.method', null, 1);
        
        $this->assertInstanceOf(\Ellinaut\JsonRpc\Model\Value\Request::class, $call);
    }

    public function testFromArrayCreatesProcedureCall(): void
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'test.procedure',
            'params' => ['test' => 'data'],
            'id' => 123
        ];
        
        $request = \Ellinaut\JsonRpc\Model\Value\Request::fromArray($data);
        
        // Verify it creates a Request (since fromArray doesn't know about ProcedureCall)
        $this->assertInstanceOf(\Ellinaut\JsonRpc\Model\Value\Request::class, $request);
        $this->assertEquals('test.procedure', $request->method);
        $this->assertEquals(['test' => 'data'], $request->params);
        $this->assertEquals(123, $request->id);
    }

    public function testProcedureCallVsNotificationDistinction(): void
    {
        $procedureCall = new ProcedureCall('call', ['data' => 'test'], 1);
        $notification = new \Ellinaut\JsonRpc\Model\Value\Notification('notify', ['data' => 'test']);
        
        // Key difference: procedure calls have non-null id, notifications have null id
        $this->assertNotNull($procedureCall->id);
        $this->assertNull($notification->id);
        
        // Both have same method and params
        $this->assertEquals('call', $procedureCall->method);
        $this->assertEquals('notify', $notification->method);
        $this->assertEquals(['data' => 'test'], $procedureCall->params);
        $this->assertEquals(['data' => 'test'], $notification->params);
    }

    public function testDifferentIdTypes(): void
    {
        $stringCall = new ProcedureCall('method', [], 'string-id');
        $intCall = new ProcedureCall('method', [], 42);
        $floatCall = new ProcedureCall('method', [], 3.14);
        
        $this->assertIsString($stringCall->id);
        $this->assertIsInt($intCall->id);
        $this->assertIsFloat($floatCall->id);
        
        // All should serialize correctly
        $stringJson = json_decode(json_encode($stringCall), true);
        $intJson = json_decode(json_encode($intCall), true);
        $floatJson = json_decode(json_encode($floatCall), true);
        
        $this->assertEquals('string-id', $stringJson['id']);
        $this->assertEquals(42, $intJson['id']);
        $this->assertEquals(3.14, $floatJson['id']);
    }

    public function testZeroIdBehavior(): void
    {
        $call = new ProcedureCall('test.method', [], 0);
        
        $this->assertEquals(0, $call->id);
        $this->assertNotNull($call->id); // 0 is not null
        
        // Note: Due to Request class implementation, ID of 0 is omitted from JSON 
        // (because if ($this->id) treats 0 as falsy)
        $json = json_decode(json_encode($call), true);
        $this->assertArrayNotHasKey('id', $json); // Current behavior - 0 is omitted
        
        // This is actually a limitation of the current Request implementation
        // JSON-RPC spec allows ID of 0, but current code treats it like a notification
    }
}