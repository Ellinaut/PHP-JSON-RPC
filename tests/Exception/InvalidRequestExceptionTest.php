<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\InvalidRequestException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class InvalidRequestExceptionTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $exception = new InvalidRequestException();

        $this->assertEquals('Invalid request object', $exception->getMessage());
        $this->assertEquals(-32600, $exception->getCode());
        $this->assertNull($exception->data);
    }

    public function testConstructorWithCustomValues(): void
    {
        $exception = new InvalidRequestException('Method is required and must be a string', ['field' => 'method']);

        $this->assertEquals('Method is required and must be a string', $exception->getMessage());
        $this->assertEquals(-32600, $exception->getCode());
        $this->assertEquals(['field' => 'method'], $exception->data);
    }
}