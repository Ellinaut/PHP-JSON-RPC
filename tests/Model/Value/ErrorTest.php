<?php

namespace Ellinaut\JsonRpc\Tests\Model\Value;

use Ellinaut\JsonRpc\Model\Value\Error;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class ErrorTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $error = new Error(-32600, 'Invalid Request', ['details' => 'Missing jsonrpc field']);

        $this->assertEquals(-32600, $error->code);
        $this->assertEquals('Invalid Request', $error->message);
        $this->assertEquals(['details' => 'Missing jsonrpc field'], $error->data);
    }

    public function testConstructorWithNullData(): void
    {
        $error = new Error(-32601, 'Method not found', null);

        $this->assertEquals(-32601, $error->code);
        $this->assertEquals('Method not found', $error->message);
        $this->assertNull($error->data);
    }

    public function testConstructorWithDifferentDataTypes(): void
    {
        // Test with string data
        $error1 = new Error(-32602, 'Invalid params', 'Parameter "id" is required');
        $this->assertEquals('Parameter "id" is required', $error1->data);

        // Test with array data
        $error2 = new Error(-32603, 'Internal error', ['stack' => 'trace']);
        $this->assertEquals(['stack' => 'trace'], $error2->data);

        // Test with object data
        $data = new \stdClass();
        $data->field = 'value';
        $error3 = new Error(-32000, 'Server error', $data);
        $this->assertEquals($data, $error3->data);
    }

    public function testReadonlyProperties(): void
    {
        $error = new Error(-32600, 'Test message', ['test' => 'data']);

        // Properties should be accessible
        $this->assertEquals(-32600, $error->code);
        $this->assertEquals('Test message', $error->message);
        $this->assertEquals(['test' => 'data'], $error->data);
    }
}