<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\JsonRcpException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class JsonRcpExceptionTest extends TestCase
{
    public function testConstructorWithAllParameters(): void
    {
        $exception = new JsonRcpException('Test message', -32000, ['key' => 'value']);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(-32000, $exception->getCode());
        $this->assertEquals(['key' => 'value'], $exception->data);
    }

    public function testConstructorWithoutData(): void
    {
        $exception = new JsonRcpException('Test message', -32000);

        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(-32000, $exception->getCode());
        $this->assertNull($exception->data);
    }
}