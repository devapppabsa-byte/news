<?php

namespace App\Exceptions;

use RuntimeException;

class SearchProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        string $message,
    ) {
        parent::__construct("[$provider] $message");
    }
}
