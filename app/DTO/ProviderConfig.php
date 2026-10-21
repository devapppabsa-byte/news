<?php

namespace App\DTO;

use App\Enums\AISearchMode;

class ProviderConfig
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $provider,
        public readonly string $model,
        public readonly AISearchMode $searchMode = AISearchMode::External,
        public readonly array $parameters = [],
        public readonly int $maxTokens = 1024,
        public readonly int $timeout = 30,
        public readonly ?string $fallbackProvider = null,
        public readonly ?string $fallbackModel = null,
    ) {}

    public function hasFallback(): bool
    {
        return filled($this->fallbackProvider);
    }

    /**
     * The fallback configuration, if a fallback provider is defined.
     */
    public function fallback(): ?self
    {
        if (! $this->hasFallback()) {
            return null;
        }

        return new self(
            provider: (string) $this->fallbackProvider,
            model: $this->fallbackModel ?? $this->model,
            searchMode: $this->searchMode,
            parameters: $this->parameters,
            maxTokens: $this->maxTokens,
            timeout: $this->timeout,
        );
    }
}
