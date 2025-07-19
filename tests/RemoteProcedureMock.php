<?php

namespace Ellinaut\JsonRpc\Tests;

use Ellinaut\JsonRpc\Server\RemoteProcedure;

/**
 * Mock implementation of RemoteProcedure for testing
 *
 * @author Philipp Marien
 */
class RemoteProcedureMock implements RemoteProcedure
{
    private mixed $result;
    private ?\Throwable $exception = null;
    private array $executionHistory = [];

    public function __construct(mixed $result = null)
    {
        $this->result = $result;
    }

    public function execute(array $params, string|int|float|null $id): mixed
    {
        // Store execution history for assertions
        $this->executionHistory[] = [
            'params' => $params,
            'id' => $id
        ];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->result;
    }

    /**
     * Set the result to be returned by execute()
     */
    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }

    /**
     * Set an exception to be thrown by execute()
     */
    public function setException(\Throwable $exception): void
    {
        $this->exception = $exception;
    }

    /**
     * Get the history of execute() calls
     */
    public function getExecutionHistory(): array
    {
        return $this->executionHistory;
    }

    /**
     * Get the last execution call
     */
    public function getLastExecution(): ?array
    {
        return end($this->executionHistory) ?: null;
    }

    /**
     * Reset the execution history
     */
    public function resetHistory(): void
    {
        $this->executionHistory = [];
    }

    /**
     * Check if execute was called with specific params
     */
    public function wasCalledWith(array $params, string|int|float|null $id = null): bool
    {
        foreach ($this->executionHistory as $execution) {
            if ($execution['params'] === $params &&
                ($id === null || $execution['id'] === $id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the number of times execute was called
     */
    public function getCallCount(): int
    {
        return count($this->executionHistory);
    }
}