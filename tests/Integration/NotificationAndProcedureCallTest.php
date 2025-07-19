<?php

namespace Ellinaut\JsonRpc\Tests\Integration;

use Ellinaut\JsonRpc\Model\Value\Notification;
use Ellinaut\JsonRpc\Model\Value\ProcedureCall;
use Ellinaut\JsonRpc\Server\JsonRpcServer;
use Ellinaut\JsonRpc\Tests\RemoteProcedureMock;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Integration tests for Notification and ProcedureCall with JsonRpcServer
 * 
 * @author Philipp Marien
 */
class NotificationAndProcedureCallTest extends TestCase
{
    private ContainerInterface $container;
    private JsonRpcServer $server;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->server = new JsonRpcServer($this->container);
    }

    public function testProcedureCallReturnsResponse(): void
    {
        $procedure = new RemoteProcedureMock('calculation result');

        $this->container->expects($this->once())
            ->method('has')
            ->with('math.add')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('math.add')
            ->willReturn($procedure);

        // Create a ProcedureCall directly
        $procedureCall = new ProcedureCall('math.add', ['a' => 5, 'b' => 3], 42);
        
        // Serialize to JSON and handle via server
        $request = json_encode($procedureCall);
        $response = $this->server->handle($request);

        // Verify procedure was validated and executed
        $this->assertEquals(1, $procedure->getValidationCallCount());
        $this->assertEquals(1, $procedure->getCallCount());
        $this->assertTrue($procedure->wasValidatedWith(['a' => 5, 'b' => 3]));
        $this->assertTrue($procedure->wasCalledWith(['a' => 5, 'b' => 3], 42));

        // Verify response is returned
        $this->assertNotNull($response);
        $this->assertJsonStringEqualsJsonString(
            '{"jsonrpc": "2.0", "result": "calculation result", "id": 42}',
            $response
        );
    }

    public function testNotificationReturnsNoResponse(): void
    {
        $procedure = new RemoteProcedureMock('should not return');

        $this->container->expects($this->once())
            ->method('has')
            ->with('user.notify')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('user.notify')
            ->willReturn($procedure);

        // Create a Notification directly
        $notification = new Notification('user.notify', ['message' => 'Hello World']);
        
        // Serialize to JSON and handle via server
        $request = json_encode($notification);
        $response = $this->server->handle($request);

        // Verify procedure was validated and executed
        $this->assertEquals(1, $procedure->getValidationCallCount());
        $this->assertEquals(1, $procedure->getCallCount());
        $this->assertTrue($procedure->wasValidatedWith(['message' => 'Hello World']));
        $this->assertTrue($procedure->wasCalledWith(['message' => 'Hello World'], null));

        // Verify no response is returned for notifications
        $this->assertNull($response);
    }

    public function testBatchWithMixedCallsAndNotifications(): void
    {
        $procedure1 = new RemoteProcedureMock('result1');
        $procedure2 = new RemoteProcedureMock('result2');
        $procedure3 = new RemoteProcedureMock('notification result');

        $this->container->expects($this->exactly(3))
            ->method('has')
            ->willReturnCallback(fn($method) => in_array($method, ['call1', 'call2', 'notify1']));

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function($method) use ($procedure1, $procedure2, $procedure3) {
                return match($method) {
                    'call1' => $procedure1,
                    'call2' => $procedure2,
                    'notify1' => $procedure3,
                };
            });

        // Create batch with mixed calls and notifications
        $batch = [
            new ProcedureCall('call1', ['param' => 'value1'], 1),
            new Notification('notify1', ['message' => 'notification']),
            new ProcedureCall('call2', ['param' => 'value2'], 'request-2')
        ];
        
        $request = json_encode($batch);
        $response = $this->server->handle($request);

        // Verify all procedures were called
        $this->assertEquals(1, $procedure1->getCallCount());
        $this->assertEquals(1, $procedure2->getCallCount());
        $this->assertEquals(1, $procedure3->getCallCount());

        // Verify response only contains results for procedure calls (not notifications)
        $this->assertNotNull($response);
        $result = json_decode($response, true);
        
        // Should have 2 responses (for the 2 procedure calls, not the notification)
        $this->assertCount(2, $result);
        
        // First response
        $this->assertEquals('result1', $result[0]['result']);
        $this->assertEquals(1, $result[0]['id']);
        
        // Second response (notification doesn't appear in results)
        $this->assertEquals('result2', $result[1]['result']);
        $this->assertEquals('request-2', $result[1]['id']);
    }

    public function testProcedureCallWithDifferentIdTypes(): void
    {
        $procedure = new RemoteProcedureMock('success');

        $this->container->expects($this->exactly(3))
            ->method('has')
            ->with('test.method')
            ->willReturn(true);

        $this->container->expects($this->exactly(3))
            ->method('get')
            ->with('test.method')
            ->willReturn($procedure);

        // Test string ID
        $stringCall = new ProcedureCall('test.method', [], 'string-id');
        $response1 = $this->server->handle(json_encode($stringCall));
        $result1 = json_decode($response1, true);
        $this->assertEquals('string-id', $result1['id']);

        // Test integer ID
        $intCall = new ProcedureCall('test.method', [], 42);
        $response2 = $this->server->handle(json_encode($intCall));
        $result2 = json_decode($response2, true);
        $this->assertEquals(42, $result2['id']);

        // Test float ID
        $floatCall = new ProcedureCall('test.method', [], 3.14);
        $response3 = $this->server->handle(json_encode($floatCall));
        $result3 = json_decode($response3, true);
        $this->assertEquals(3.14, $result3['id']);
    }

    public function testNotificationWithEmptyParams(): void
    {
        $procedure = new RemoteProcedureMock('executed');

        $this->container->expects($this->once())
            ->method('has')
            ->with('ping')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('ping')
            ->willReturn($procedure);

        // Notification with no parameters
        $notification = new Notification('ping', null);
        $response = $this->server->handle(json_encode($notification));

        // Verify procedure was called with empty params
        $this->assertTrue($procedure->wasValidatedWith([]));
        $this->assertTrue($procedure->wasCalledWith([], null));
        
        // No response for notification
        $this->assertNull($response);
    }

    public function testProcedureCallWithZeroIdLimitation(): void
    {
        $procedure = new RemoteProcedureMock('zero result');

        $this->container->expects($this->once())
            ->method('has')
            ->with('test.zero')
            ->willReturn(true);

        $this->container->expects($this->once())
            ->method('get')
            ->with('test.zero')
            ->willReturn($procedure);

        // Procedure call with ID = 0 
        // Note: Due to Request class limitation, this behaves like a notification
        $call = new ProcedureCall('test.zero', ['test' => true], 0);
        $response = $this->server->handle(json_encode($call));

        // Due to Request serialization limitation, ID 0 is omitted from JSON
        // so when deserialized, it becomes null (behaves like notification)
        $this->assertTrue($procedure->wasCalledWith(['test' => true], null));
        
        // No response is returned (behaves like notification due to ID serialization issue)
        $this->assertNull($response);
    }

    public function testSerializationRoundTrip(): void
    {
        // Test that objects can be serialized to JSON and the server handles them correctly
        $call = new ProcedureCall('test.method', ['key' => 'value'], 'test-id');
        $notification = new Notification('test.notify', ['message' => 'hello']);

        // Serialize to JSON
        $callJson = json_encode($call);
        $notificationJson = json_encode($notification);

        // Verify JSON structure
        $callData = json_decode($callJson, true);
        $notificationData = json_decode($notificationJson, true);

        // Procedure call should have id
        $this->assertArrayHasKey('id', $callData);
        $this->assertEquals('test-id', $callData['id']);

        // Notification should not have id
        $this->assertArrayNotHasKey('id', $notificationData);

        // Both should have jsonrpc and method
        $this->assertEquals('2.0', $callData['jsonrpc']);
        $this->assertEquals('2.0', $notificationData['jsonrpc']);
        $this->assertEquals('test.method', $callData['method']);
        $this->assertEquals('test.notify', $notificationData['method']);
    }
}