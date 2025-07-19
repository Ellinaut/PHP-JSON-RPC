<?php

namespace Ellinaut\JsonRpc\Tests;

use Ellinaut\JsonRpc\Client\TransportInterface;

/**
 * @author Philipp Marien
 */
class TransportMock implements TransportInterface
{
    private array $responses = [];
    private array $sentRequests = [];
    private int $currentResponseIndex = 0;

    public function addResponse(?string $response): void
    {
        $this->responses[] = $response;
    }

    public function send(string $json): ?string
    {
        $this->sentRequests[] = $json;
        
        if ($this->currentResponseIndex >= count($this->responses)) {
            return null;
        }
        
        $response = $this->responses[$this->currentResponseIndex];
        $this->currentResponseIndex++;
        
        return $response;
    }

    public function getSentRequests(): array
    {
        return $this->sentRequests;
    }

    public function getLastSentRequest(): ?string
    {
        return end($this->sentRequests) ?: null;
    }

    public function reset(): void
    {
        $this->responses = [];
        $this->sentRequests = [];
        $this->currentResponseIndex = 0;
    }
}