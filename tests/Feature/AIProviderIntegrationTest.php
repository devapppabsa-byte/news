<?php

use App\DTO\AIAnalysisRequest;
use App\Enums\AIOperation;
use App\Exceptions\AIProviderException;
use App\Services\AI\Providers\AnthropicAnalysisProvider;
use App\Services\AI\Providers\OpenAIAnalysisProvider;
use App\Services\AI\Providers\OpenRouterAnalysisProvider;

test('a real openai analysis returns a summary', function () {
    if (blank(config('services.openai.key'))) {
        $this->markTestSkipped('OPENAI_API_KEY is not configured.');
    }

    try {
        $result = (new OpenAIAnalysisProvider)->analyze(new AIAnalysisRequest(
            operation: AIOperation::Summarize,
            payload: ['text' => 'Laravel released a new version with performance improvements.'],
            timeout: 60,
            retries: 0,
        ));
    } catch (AIProviderException $e) {
        if (str_contains($e->getMessage(), 'status 402') || str_contains($e->getMessage(), 'status 429')) {
            $this->markTestSkipped('OpenAI account is unavailable: '.$e->getMessage());
        }

        throw $e;
    }

    expect($result->data)->toHaveKey('text')
        ->and($result->provider)->toBe('openai');
})->group('integration');

test('a real anthropic analysis returns a summary', function () {
    if (blank(config('services.anthropic.key'))) {
        $this->markTestSkipped('ANTHROPIC_API_KEY is not configured.');
    }

    try {
        $result = (new AnthropicAnalysisProvider)->analyze(new AIAnalysisRequest(
            operation: AIOperation::Summarize,
            payload: ['text' => 'Laravel released a new version with performance improvements.'],
            timeout: 60,
            retries: 0,
        ));
    } catch (AIProviderException $e) {
        if (str_contains($e->getMessage(), 'status 402') || str_contains($e->getMessage(), 'status 429')) {
            $this->markTestSkipped('Anthropic account is unavailable: '.$e->getMessage());
        }

        throw $e;
    }

    expect($result->data)->toHaveKey('text')
        ->and($result->provider)->toBe('anthropic');
})->group('integration');

test('a real openrouter analysis returns a summary without exposing the key', function () {
    if (blank(config('services.openrouter.key'))) {
        $this->markTestSkipped('OPENROUTER_API_KEY is not configured.');
    }

    try {
        $result = (new OpenRouterAnalysisProvider)->analyze(new AIAnalysisRequest(
            operation: AIOperation::Summarize,
            payload: ['text' => 'Laravel released a new version with performance improvements.'],
            timeout: 120,
            retries: 0,
        ));
    } catch (AIProviderException $e) {
        if (str_contains($e->getMessage(), 'status 402')
            || str_contains($e->getMessage(), 'status 401')) {
            $this->markTestSkipped('OpenRouter account/key is unavailable: '.$e->getMessage());
        }

        throw $e;
    }

    expect(json_encode($result->data))->not->toContain('sk-or-v1')
        ->and($result->provider)->toBe('openrouter');
})->group('integration');
