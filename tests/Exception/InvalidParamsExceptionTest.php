<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\InvalidParamsException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class InvalidParamsExceptionTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $exception = new InvalidParamsException();

        $this->assertEquals('Invalid method parameter(s)', $exception->getMessage());
        $this->assertEquals(-32602, $exception->getCode());
        $this->assertNull($exception->data);
    }

    public function testConstructorWithCustomValues(): void
    {
        $exception = new InvalidParamsException('Missing required parameter: id', ['missing' => 'id']);

        $this->assertEquals('Missing required parameter: id', $exception->getMessage());
        $this->assertEquals(-32602, $exception->getCode());
        $this->assertEquals(['missing' => 'id'], $exception->data);
    }
}