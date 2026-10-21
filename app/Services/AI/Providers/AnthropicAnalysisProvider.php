<?php

namespace App\Services\AI\Providers;

use App\DTO\AIAnalysisRequest;
use App\DTO\AIAnalysisResult;
use App\Enums\AICapability;
use App\Enums\AISearchMode;
use App\Exceptions\AIProviderException;
use Illuminate\Support\Arr;

class AnthropicAnalysisProvider extends AbstractAIProvider
{
    protected const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    public function key(): string
    {
        return 'anthropic';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.anthropic.key')));
    }

    public function analyze(AIAnalysisRequest $request): AIAnalysisResult
    {
        $payload = [
            'model' => $this->model($request) ?: (string) config('services.anthropic.model', 'claude-3-5-sonnet-latest'),
            'max_tokens' => $request->maxTokens ?? (int) config('ai.max_tokens', 1024),
            'messages' => [
                ['role' => 'user', 'content' => $this->instruction($request)."\n\n".$this->prompt($request)],
            ],
        ];

        if ($request->systemPrompt !== null) {
            $payload['system'] = $request->systemPrompt;
        }

        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }

        $this->applyIntegratedSearch($request, $payload);

        $response = $this->run(fn () => $this->http($request)
            ->withHeaders([
                'x-api-key' => (string) $this->credential('key', config('services.anthropic.key')),
                'anthropic-version' => (string) config('services.anthropic.version', '2023-06-01'),
            ])
            ->post((string) config('services.anthropic.base_url', static::ENDPOINT), $payload));

        $this->ensureSuccessful($response);

        return $this->toResult($response->json() ?? [], $request);
    }

    /**
     * Enable Anthropic's server-side web search tool when requested.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function applyIntegratedSearch(AIAnalysisRequest $request, array &$payload): void
    {
        if (($request->parameters['search_mode'] ?? null) !== AISearchMode::Integrated->value) {
            return;
        }

        if (! $this->supports(AICapability::WebSearch)) {
            throw new AIProviderException($this->key(), 'Integrated web search is not supported by the configured model.');
        }

        $payload['tools'] = [[
            'type' => 'web_search_20250305',
            'name' => 'web_search',
            'max_uses' => 5,
        ]];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toResult(array $payload, AIAnalysisRequest $request): AIAnalysisResult
    {
        $content = collect(Arr::get($payload, 'content', []))
            ->filter(fn (array $block): bool => ($block['type'] ?? null) === 'text')
            ->pluck('text')
            ->implode("\n");

        $data = $request->expectsStructuredOutput() ? $this->decode($content) : ['text' => trim($content)];

        return new AIAnalysisResult(
            operation: $request->operation,
            data: $this->validate($data, $request),
            raw: ['citations' => Arr::get($payload, 'content', [])],
            model: Arr::get($payload, 'model'),
            provider: $this->key(),
            promptTokens: Arr::get($payload, 'usage.input_tokens'),
            completionTokens: Arr::get($payload, 'usage.output_tokens'),
        );
    }

    /**
     * @return array<int, AICapability>
     */
    protected function defaultCapabilities(): array
    {
        return [
            AICapability::StructuredOutput,
            AICapability::FunctionCalling,
            AICapability::WebSearch,
        ];
    }
}
