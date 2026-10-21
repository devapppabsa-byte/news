<?php

namespace App\Services\AI;

use App\Contracts\AIProvider;
use App\DTO\AIAnalysisRequest;
use App\DTO\AIAnalysisResult;
use App\DTO\ProviderConfig;
use App\Exceptions\AIProviderException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AIProviderRegistry
{
    /**
     * @param  iterable<AIProvider>  $providers
     */
    public function __construct(
        protected iterable $providers,
        protected AIConfigRepository $config,
    ) {}

    /**
     * The providers that are active in config and fully configured.
     *
     * @return Collection<int, AIProvider>
     */
    public function enabled(): Collection
    {
        $active = config('ai.providers', []);

        return collect($this->providers)
            ->filter(fn (AIProvider $provider): bool => in_array($provider->key(), $active, true))
            ->filter(fn (AIProvider $provider): bool => $provider->isConfigured())
            ->values();
    }

    /**
     * Resolve a provider by key, or the provider from the active configuration.
     */
    public function resolve(?string $key = null): ?AIProvider
    {
        $key ??= $this->config->current()->provider;

        return $this->enabled()->first(fn (AIProvider $provider): bool => $provider->key() === $key);
    }

    /**
     * Run the configured provider, falling back to the backup provider when
     * the primary fails and a fallback is configured.
     *
     * @throws AIProviderException
     */
    public function analyze(AIAnalysisRequest $request, ?ProviderConfig $config = null): AIAnalysisResult
    {
        $config ??= $this->config->current();
        $fallbacks = $config->hasFallback() ? $config->fallback() : null;

        try {
            return $this->call($request, $config);
        } catch (AIProviderException $exception) {
            if ($fallbacks === null) {
                throw $exception;
            }

            Log::warning('AI provider failed, trying fallback', [
                'provider' => $config->provider,
                'message' => $exception->getMessage(),
            ]);

            return $this->call($request, $fallbacks);
        }
    }

    /**
     * @throws AIProviderException
     */
    protected function call(AIAnalysisRequest $request, ProviderConfig $config): AIAnalysisResult
    {
        $provider = $this->resolve($config->provider);

        if ($provider === null) {
            throw new AIProviderException($config->provider, 'Provider is not enabled or not configured.');
        }

        $request = new AIAnalysisRequest(
            operation: $request->operation,
            payload: $request->payload,
            schema: $request->schema,
            systemPrompt: $request->systemPrompt,
            model: $config->model,
            maxTokens: $request->maxTokens ?? $config->maxTokens,
            temperature: $request->temperature,
            timeout: $request->timeout !== 30 ? $request->timeout : $config->timeout,
            retries: $request->retries,
            parameters: array_merge($config->parameters, $request->parameters, [
                'search_mode' => $config->searchMode->value,
            ]),
        );

        return $provider->analyze($request);
    }
}
