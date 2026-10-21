<?php

use App\DTO\AIAnalysisRequest;
use App\Enums\AICapability;
use App\Enums\AIOperation;
use App\Exceptions\AIProviderException;
use App\Services\AI\Providers\AnthropicAnalysisProvider;
use App\Services\AI\Providers\OpenAIAnalysisProvider;
use App\Services\AI\Providers\OpenRouterAnalysisProvider;

function aiRequest(array $overrides = []): AIAnalysisRequest
{
    return new AIAnalysisRequest(...array_merge([
        'operation' => AIOperation::Summarize,
        'payload' => ['text' => 'A short piece of news.'],
        'timeout' => 5,
        'retries' => 0,
    ], $overrides));
}

test('openai parses a summarize response', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'model' => 'gpt-4o-mini',
            'choices' => [['message' => ['content' => 'A concise summary.']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ]),
    ]);
    config()->set('services.openai.key', 'sk-openai-secret');

    $result = (new OpenAIAnalysisProvider)->analyze(aiRequest());

    expect($result->data['text'])->toBe('A concise summary.')
        ->and($result->provider)->toBe('openai')
        ->and($result->model)->toBe('gpt-4o-mini')
        ->and($result->totalTokens())->toBe(15);

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-openai-secret')
        && $request['model'] === 'gpt-4o-mini');
});

test('openai decodes structured json output and validates the schema', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => '```json'."\n".'{"relevance": 80, "reason": "on topic"}'."\n".'```']]],
        ]),
    ]);
    config()->set('services.openai.key', 'sk-openai-secret');

    $result = (new OpenAIAnalysisProvider)->analyze(aiRequest([
        'operation' => AIOperation::ClassifyRelevance,
        'schema' => [
            'type' => 'object',
            'required' => ['relevance'],
            'properties' => ['relevance' => ['type' => 'integer']],
        ],
    ]));

    expect($result->data['relevance'])->toBe(80);
});

test('openai rejects structured output that violates the schema', function () {
    Http::fake([
        'api.openai.com/*' => Http::response([
            'choices' => [['message' => ['content' => '{"relevance": "high"}']]],
        ]),
    ]);
    config()->set('services.openai.key', 'sk-openai-secret');

    (new OpenAIAnalysisProvider)->analyze(aiRequest([
        'operation' => AIOperation::ClassifyRelevance,
        'schema' => [
            'type' => 'object',
            'required' => ['relevance'],
            'properties' => ['relevance' => ['type' => 'integer']],
        ],
    ]));
})->throws(AIProviderException::class);

test('openai reports an http failure without leaking the key', function () {
    Http::fake([
        'api.openai.com/*' => Http::response(['error' => ['message' => 'Incorrect API key sk-openai-secret provided']], 401),
    ]);
    config()->set('services.openai.key', 'sk-openai-secret');

    try {
        (new OpenAIAnalysisProvider)->analyze(aiRequest());
        $this->fail('Expected an AIProviderException.');
    } catch (AIProviderException $exception) {
        expect($exception->getMessage())
            ->toContain('status 401')
            ->not->toContain('sk-openai-secret');
    }
});

test('openrouter uses its headers and parses json mode output', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'model' => 'openai/gpt-4o-mini',
            'choices' => [['message' => ['content' => '{"text": "ok"}']]],
        ]),
    ]);
    config()->set('services.openrouter.key', 'sk-or-secret');

    $result = (new OpenRouterAnalysisProvider)->analyze(aiRequest([
        'operation' => AIOperation::Analyze,
        'schema' => ['type' => 'object', 'required' => ['text']],
    ]));

    expect($result->data['text'])->toBe('ok')
        ->and($result->provider)->toBe('openrouter');

    Http::assertSent(fn ($request) => $request->hasHeader('X-Title'));
});

test('openrouter supports the integrated web plugin', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => '{"text": "ok"}']]],
        ]),
    ]);
    config()->set('services.openrouter.key', 'sk-or-secret');

    (new OpenRouterAnalysisProvider)->analyze(aiRequest([
        'parameters' => ['search_mode' => 'integrated'],
        'schema' => ['type' => 'object', 'required' => ['text']],
    ]));

    Http::assertSent(fn ($request) => ($request['plugins'][0]['id'] ?? null) === 'web');
});

test('anthropic uses its own auth headers and response shape', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'model' => 'claude-3-5-sonnet-latest',
            'content' => [['type' => 'text', 'text' => 'Anthropic summary.']],
            'usage' => ['input_tokens' => 12, 'output_tokens' => 4],
        ]),
    ]);
    config()->set('services.anthropic.key', 'sk-ant-secret');

    $result = (new AnthropicAnalysisProvider)->analyze(aiRequest());

    expect($result->data['text'])->toBe('Anthropic summary.')
        ->and($result->provider)->toBe('anthropic')
        ->and($result->totalTokens())->toBe(16);

    Http::assertSent(fn ($request) => $request->hasHeader('x-api-key', 'sk-ant-secret')
        && $request->hasHeader('anthropic-version')
        && isset($request['max_tokens'])
        && $request['messages'][0]['role'] === 'user');
});

test('anthropic enables the web search tool when integrated search is requested', function () {
    Http::fake([
        'api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'ok']],
        ]),
    ]);
    config()->set('services.anthropic.key', 'sk-ant-secret');

    (new AnthropicAnalysisProvider)->analyze(aiRequest([
        'parameters' => ['search_mode' => 'integrated'],
    ]));

    Http::assertSent(fn ($request) => ($request['tools'][0]['type'] ?? null) === 'web_search_20250305');
});

test('each provider declares its own capabilities', function () {
    expect((new AnthropicAnalysisProvider)->supports(AICapability::JsonMode))->toBeFalse()
        ->and((new AnthropicAnalysisProvider)->supports(AICapability::WebSearch))->toBeTrue()
        ->and((new OpenAIAnalysisProvider)->supports(AICapability::JsonMode))->toBeTrue()
        ->and((new OpenRouterAnalysisProvider)->supports(AICapability::FunctionCalling))->toBeFalse();
});
