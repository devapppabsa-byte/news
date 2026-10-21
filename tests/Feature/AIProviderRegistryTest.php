<?php

use App\DTO\AIAnalysisRequest;
use App\DTO\ProviderConfig;
use App\Enums\AIOperation;
use App\Enums\AISearchMode;
use App\Exceptions\AIProviderException;
use App\Models\AiSetting;
use App\Services\AI\AIConfigRepository;
use App\Services\AI\AIProviderRegistry;

function aiConfigRequest(): AIAnalysisRequest
{
    return new AIAnalysisRequest(
        operation: AIOperation::Summarize,
        payload: ['text' => 'news'],
        timeout: 5,
        retries: 0,
    );
}

test('the registry resolves the configured provider', function () {
    Http::fake(['api.openai.com/*' => Http::response([
        'choices' => [['message' => ['content' => 'ok']]],
    ])]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('ai.provider', 'openai');

    (new AIConfigRepository)->forget();

    $result = app(AIProviderRegistry::class)->analyze(aiConfigRequest());

    expect($result->provider)->toBe('openai');
});

test('the registry falls back to the configured provider on failure', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => 'fallback ok']]],
        ]),
    ]);
    config()->set('ai.providers', ['openai', 'openrouter']);
    config()->set('services.openai.key', 'sk-openai-secret');
    config()->set('services.openrouter.key', 'sk-or-secret');

    $config = new ProviderConfig(
        provider: 'openai',
        model: 'gpt-4o-mini',
        searchMode: AISearchMode::External,
        fallbackProvider: 'openrouter',
        fallbackModel: 'openai/gpt-4o-mini',
    );

    $result = app(AIProviderRegistry::class)->analyze(aiConfigRequest(), $config);

    expect($result->provider)->toBe('openrouter')
        ->and($result->data['text'])->toBe('fallback ok');
});

test('the registry surfaces the error when no fallback is configured', function () {
    Http::fake(['api.openai.com/*' => Http::response(['error' => ['message' => 'boom']], 500)]);
    config()->set('ai.providers', ['openai']);
    config()->set('services.openai.key', 'sk-openai-secret');

    app(AIProviderRegistry::class)->analyze(aiConfigRequest(), new ProviderConfig(
        provider: 'openai',
        model: 'gpt-4o-mini',
    ));
})->throws(AIProviderException::class);

test('the config repository prefers the database row over env defaults', function () {
    AiSetting::factory()->create([
        'provider' => 'anthropic',
        'model' => 'claude-3-5-sonnet-latest',
        'search_mode' => AISearchMode::Integrated,
    ]);

    $config = (new AIConfigRepository)->current();

    expect($config->provider)->toBe('anthropic')
        ->and($config->searchMode)->toBe(AISearchMode::Integrated);
});

test('putting a new configuration deactivates previous rows', function () {
    AiSetting::factory()->count(2)->create(['is_active' => true]);

    app(AIConfigRepository::class)->put([
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'search_mode' => 'external',
        'parameters' => [],
        'max_tokens' => 1024,
        'timeout' => 30,
    ]);

    expect(AiSetting::query()->where('is_active', true)->count())->toBe(1)
        ->and(AiSetting::query()->where('is_active', true)->first()->provider)->toBe('openai');
});
