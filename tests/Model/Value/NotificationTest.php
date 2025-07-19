<?php

namespace Ellinaut\JsonRpc\Tests\Model\Value;

use Ellinaut\JsonRpc\Model\Value\Notification;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class NotificationTest extends TestCase
{
    public function testConstructorWithMethod(): void
    {
        $notification = new Notification('test.method', null);
        
        $this->assertEquals('test.method', $notification->method);
        $this->assertNull($notification->params);
        $this->assertNull($notification->id);
    }

    public function testConstructorWithMethodAndParams(): void
    {
        $params = ['param1' => 'value1', 'param2' => 42];
        $notification = new Notification('test.method', $params);
        
        $this->assertEquals('test.method', $notification->method);
        $this->assertEquals($params, $notification->params);
        $this->assertNull($notification->id);
    }

    public function testConstructorWithPositionalParams(): void
    {
        $params = ['value1', 42, true];
        $notification = new Notification('test.method', $params);
        
        $this->assertEquals('test.method', $notification->method);
        $this->assertEquals($params, $notification->params);
        $this->assertNull($notification->id);
    }

    public function testJsonSerializeWithoutParams(): void
    {
        $notification = new Notification('test.notification', null);
        $json = json_encode($notification);
        
        $expected = '{"jsonrpc":"2.0","method":"test.notification"}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithNamedParams(): void
    {
        $params = ['name' => 'John', 'age' => 30];
        $notification = new Notification('user.update', $params);
        $json = json_encode($notification);
        
        $expected = '{"jsonrpc":"2.0","method":"user.update","params":{"name":"John","age":30}}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithPositionalParams(): void
    {
        $params = ['John', 30, true];
        $notification = new Notification('user.create', $params);
        $json = json_encode($notification);
        
        $expected = '{"jsonrpc":"2.0","method":"user.create","params":["John",30,true]}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testJsonSerializeWithEmptyParams(): void
    {
        $notification = new Notification('test.method', []);
        $json = json_encode($notification);
        
        // Empty params array is omitted from JSON (Request class behavior)
        $expected = '{"jsonrpc":"2.0","method":"test.method"}';
        $this->assertJsonStringEqualsJsonString($expected, $json);
    }

    public function testIdIsAlwaysNull(): void
    {
        $notification = new Notification('any.method', ['param' => 'value']);
        
        // Verify that id is always null (this is what makes it a notification)
        $this->assertNull($notification->id);
        
        // Verify the JSON doesn't contain an id field
        $json = json_decode(json_encode($notification), true);
        $this->assertArrayNotHasKey('id', $json);
    }

    public function testInheritsFromRequest(): void
    {
        $notification = new Notification('test.method', null);
        
        $this->assertInstanceOf(\Ellinaut\JsonRpc\Model\Value\Request::class, $notification);
    }

    public function testFromArrayCreatesNotification(): void
    {
        $data = [
            'jsonrpc' => '2.0',
            'method' => 'test.notification',
            'params' => ['test' => 'data']
        ];
        
        $request = \Ellinaut\JsonRpc\Model\Value\Request::fromArray($data);
        
        // Verify it creates a Request (since fromArray doesn't know about Notification)
        $this->assertInstanceOf(\Ellinaut\JsonRpc\Model\Value\Request::class, $request);
        $this->assertEquals('test.notification', $request->method);
        $this->assertEquals(['test' => 'data'], $request->params);
        $this->assertNull($request->id);
    }

    public function testNotificationVsProcedureCallDistinction(): void
    {
        $notification = new Notification('notify', ['data' => 'test']);
        $procedureCall = new \Ellinaut\JsonRpc\Model\Value\ProcedureCall('call', ['data' => 'test'], 1);
        
        // Key difference: notifications have null id, procedure calls have non-null id
        $this->assertNull($notification->id);
        $this->assertNotNull($procedureCall->id);
        
        // Both have same method and params
        $this->assertEquals('notify', $notification->method);
        $this->assertEquals('call', $procedureCall->method);
        $this->assertEquals(['data' => 'test'], $notification->params);
        $this->assertEquals(['data' => 'test'], $procedureCall->params);
    }
}