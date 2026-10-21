<?php

namespace App\Services\AI\Providers;

use App\DTO\AIAnalysisRequest;
use App\DTO\AIAnalysisResult;
use App\Enums\AICapability;
use App\Enums\AISearchMode;
use App\Exceptions\AIProviderException;
use Illuminate\Support\Arr;

class OpenAIAnalysisProvider extends AbstractAIProvider
{
    protected const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function key(): string
    {
        return 'openai';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.openai.key')));
    }

    public function analyze(AIAnalysisRequest $request): AIAnalysisResult
    {
        $payload = [
            'model' => $this->model($request) ?: (string) config('services.openai.model', 'gpt-4o-mini'),
            'messages' => array_values(array_filter([
                $request->systemPrompt !== null
                    ? ['role' => 'system', 'content' => $request->systemPrompt]
                    : null,
                ['role' => 'user', 'content' => $this->instruction($request)."\n\n".$this->prompt($request)],
            ])),
        ];

        if ($request->maxTokens !== null) {
            $payload['max_tokens'] = $request->maxTokens;
        }

        if ($request->temperature !== null) {
            $payload['temperature'] = $request->temperature;
        }

        if ($request->expectsStructuredOutput() && $this->supports(AICapability::JsonMode)) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $this->applyIntegratedSearch($request, $payload);

        $response = $this->run(fn () => $this->http($request)
            ->withToken((string) $this->credential('key', config('services.openai.key')))
            ->post((string) config('services.openai.base_url', static::ENDPOINT), $payload));

        $this->ensureSuccessful($response);

        return $this->toResult($response->json() ?? [], $request);
    }

    /**
     * Enable the provider's built-in web search when requested and supported.
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

        $payload['tools'] = [['type' => 'web_search']];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function toResult(array $payload, AIAnalysisRequest $request): AIAnalysisResult
    {
        $content = (string) Arr::get($payload, 'choices.0.message.content', '');
        $data = $request->expectsStructuredOutput() ? $this->decode($content) : ['text' => trim($content)];

        return new AIAnalysisResult(
            operation: $request->operation,
            data: $this->validate($data, $request),
            raw: ['citations' => Arr::get($payload, 'choices.0.message.annotations', [])],
            model: Arr::get($payload, 'model'),
            provider: $this->key(),
            promptTokens: Arr::get($payload, 'usage.prompt_tokens'),
            completionTokens: Arr::get($payload, 'usage.completion_tokens'),
        );
    }

    /**
     * @return array<int, AICapability>
     */
    protected function defaultCapabilities(): array
    {
        return [
            AICapability::StructuredOutput,
            AICapability::JsonMode,
            AICapability::FunctionCalling,
            AICapability::WebSearch,
        ];
    }
}
