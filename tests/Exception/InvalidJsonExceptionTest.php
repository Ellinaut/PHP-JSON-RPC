<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\InvalidJsonException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class InvalidJsonExceptionTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $exception = new InvalidJsonException();

        $this->assertEquals('Invalid json received by the server', $exception->getMessage());
        $this->assertEquals(-32700, $exception->getCode());
        $this->assertNull($exception->data);
    }

    public function testConstructorWithCustomValues(): void
    {
        $exception = new InvalidJsonException('Custom parse error', ['details' => 'invalid character']);

        $this->assertEquals('Custom parse error', $exception->getMessage());
        $this->assertEquals(-32700, $exception->getCode());
        $this->assertEquals(['details' => 'invalid character'], $exception->data);
    }
}