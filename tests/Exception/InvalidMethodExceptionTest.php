<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\InvalidMethodException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class InvalidMethodExceptionTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $exception = new InvalidMethodException();

        $this->assertEquals('The requested method does not exist', $exception->getMessage());
        $this->assertEquals(-32601, $exception->getCode());
        $this->assertNull($exception->data);
    }

    public function testConstructorWithCustomValues(): void
    {
        $exception = new InvalidMethodException('Invalid method: testMethod', ['method' => 'testMethod']);

        $this->assertEquals('Invalid method: testMethod', $exception->getMessage());
        $this->assertEquals(-32601, $exception->getCode());
        $this->assertEquals(['method' => 'testMethod'], $exception->data);
    }
}