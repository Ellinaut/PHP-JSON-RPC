<?php

namespace Ellinaut\JsonRpc\Tests\Exception;

use Ellinaut\JsonRpc\Exception\InternalErrorException;
use PHPUnit\Framework\TestCase;

/**
 * @author Philipp Marien
 */
class InternalErrorExceptionTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $exception = new InternalErrorException();

        $this->assertEquals('An internal error occurred', $exception->getMessage());
        $this->assertEquals(-32603, $exception->getCode());
        $this->assertNull($exception->data);
    }

    public function testConstructorWithCustomValues(): void
    {

        $exception = new InternalErrorException('Test', ['key' => 'value']);

        $this->assertEquals('Test', $exception->getMessage());
        $this->assertEquals(-32603, $exception->getCode());
        $this->assertEquals(['key' => 'value'], $exception->data);
    }
}