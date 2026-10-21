<?php

namespace App\Services\AI;

use App\DTO\ProviderConfig;
use App\Enums\AISearchMode;
use App\Models\AiSetting;

class AIConfigRepository
{
    protected ?ProviderConfig $cache = null;

    /**
     * Resolve the active provider configuration, falling back to config/env
     * defaults when no database row is present.
     */
    public function current(): ProviderConfig
    {
        return $this->cache ??= $this->fromDatabase() ?? $this->fromConfig();
    }

    /**
     * Persist the provider configuration as the single active row.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function put(array $attributes): AiSetting
    {
        AiSetting::query()->update(['is_active' => false]);

        $setting = AiSetting::query()->create(array_merge($attributes, ['is_active' => true]));

        $this->cache = null;

        return $setting;
    }

    public function forget(): void
    {
        $this->cache = null;
    }

    protected function fromDatabase(): ?ProviderConfig
    {
        $setting = AiSetting::query()->where('is_active', true)->latest('id')->first();

        if ($setting === null) {
            return null;
        }

        return new ProviderConfig(
            provider: $setting->provider,
            model: $setting->model,
            searchMode: $setting->search_mode instanceof AISearchMode
                ? $setting->search_mode
                : AISearchMode::from((string) $setting->search_mode),
            parameters: $setting->parameters ?? [],
            maxTokens: $setting->max_tokens,
            timeout: $setting->timeout,
            fallbackProvider: $setting->fallback_provider,
            fallbackModel: $setting->fallback_model,
        );
    }

    protected function fromConfig(): ProviderConfig
    {
        return new ProviderConfig(
            provider: (string) config('ai.provider', 'openai'),
            model: (string) config('ai.model', 'gpt-4o-mini'),
            searchMode: AISearchMode::tryFrom((string) config('ai.search_mode', 'external')) ?? AISearchMode::External,
            parameters: (array) config('ai.parameters', []),
            maxTokens: (int) config('ai.max_tokens', 1024),
            timeout: (int) config('ai.timeout', 30),
            fallbackProvider: config('ai.fallback_provider'),
            fallbackModel: config('ai.fallback_model'),
        );
    }
}
