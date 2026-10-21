<?php

namespace App\DTO;

use App\Enums\AIOperation;

class AIAnalysisRequest
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $schema
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly AIOperation $operation,
        public readonly array $payload = [],
        public readonly ?array $schema = null,
        public readonly ?string $systemPrompt = null,
        public readonly ?string $model = null,
        public readonly ?int $maxTokens = null,
        public readonly ?float $temperature = null,
        public readonly int $timeout = 30,
        public readonly int $retries = 2,
        public readonly array $parameters = [],
    ) {}

    /**
     * Whether the request expects a validated structured payload.
     */
    public function expectsStructuredOutput(): bool
    {
        return $this->schema !== null
            || in_array($this->operation, [AIOperation::ExtractStructured, AIOperation::DetectDuplicates], true);
    }
}
