<?php

namespace Ellinaut\JsonRpc\Model\Value;

/**
 * @author Philipp Marien
 */
readonly class Error
{
    public function __construct(
        public int $code,
        public string $message,
        public mixed $data
    ) {
    }
}
