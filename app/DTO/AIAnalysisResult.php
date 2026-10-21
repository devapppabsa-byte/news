<?php

namespace App\DTO;

use App\Enums\AIOperation;

class AIAnalysisResult
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public readonly AIOperation $operation,
        public readonly array $data,
        public readonly array $raw = [],
        public readonly ?string $model = null,
        public readonly ?string $provider = null,
        public readonly ?int $promptTokens = null,
        public readonly ?int $completionTokens = null,
    ) {}

    public function totalTokens(): ?int
    {
        if ($this->promptTokens === null && $this->completionTokens === null) {
            return null;
        }

        return (int) $this->promptTokens + (int) $this->completionTokens;
    }
}
