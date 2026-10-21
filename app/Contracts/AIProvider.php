<?php

namespace App\Contracts;

use App\DTO\AIAnalysisRequest;
use App\DTO\AIAnalysisResult;
use App\Enums\AICapability;
use App\Exceptions\AIProviderException;

interface AIProvider
{
    /**
     * A stable identifier for the provider (e.g. "openai", "anthropic").
     */
    public function key(): string;

    /**
     * Whether the provider can honour the given capability.
     */
    public function supports(AICapability $capability): bool;

    /**
     * Whether the provider is usable (e.g. required credentials present).
     */
    public function isConfigured(): bool;

    /**
     * Run a single analysis operation and return a normalized result.
     *
     * @throws AIProviderException
     */
    public function analyze(AIAnalysisRequest $request): AIAnalysisResult;
}
