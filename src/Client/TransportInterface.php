<?php

namespace Ellinaut\JsonRpc\Client;

/**
 * @author Philipp Marien
 */
interface TransportInterface
{
    public function send(string $json): ?string;
}
