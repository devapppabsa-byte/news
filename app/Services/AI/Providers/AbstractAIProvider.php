<?php

namespace App\Services\AI\Providers;

use App\Contracts\AIProvider;
use App\DTO\AIAnalysisRequest;
use App\Enums\AICapability;
use App\Enums\AIOperation;
use App\Exceptions\AIProviderException;
use App\Services\AI\StructuredOutputValidator;
use App\Services\Search\CredentialRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

abstract class AbstractAIProvider implements AIProvider
{
    /**
     * Capabilities supported by the provider. Adapters override this.
     *
     * @return array<int, AICapability>
     */
    protected function defaultCapabilities(): array
    {
        return [];
    }

    public function supports(AICapability $capability): bool
    {
        return in_array($capability, $this->defaultCapabilities(), true);
    }

    /**
     * Resolve a credential, preferring the database (encrypted) over env.
     */
    protected function credential(string $name, ?string $fallback = null): ?string
    {
        return app(CredentialRepository::class)->get($this->key(), $name, $fallback);
    }

    /**
     * Build a configured HTTP client honouring the request timeouts/retries.
     */
    protected function http(AIAnalysisRequest $request): PendingRequest
    {
        return Http::timeout($request->timeout)
            ->connectTimeout($request->timeout)
            ->retry($request->retries, config('ai.retry_backoff_ms'), throw: false)
            ->withHeaders([
                'User-Agent' => config('ai.user_agent'),
                'Accept' => 'application/json',
            ]);
    }

    /**
     * Execute the request, normalizing transport errors to a provider exception.
     */
    protected function run(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException $exception) {
            throw new AIProviderException($this->key(), 'Connection failed: '.$this->sanitize($exception->getMessage()));
        }
    }

    /**
     * Throw a provider exception when the response indicates a failure.
     */
    protected function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $detail = $this->errorDetail($response);

        throw new AIProviderException(
            $this->key(),
            "Request failed with status {$response->status()}".($detail !== null ? ": {$detail}" : ''),
        );
    }

    /**
     * Extract a human readable error detail from an unsuccessful response.
     */
    protected function errorDetail(Response $response): ?string
    {
        $message = $response->json('error.message') ?? $response->json('message');

        return filled($message) ? Str::limit($this->sanitize((string) $message), 300) : null;
    }

    /**
     * Whether a response indicates the provider could not fulfill the request
     * because the account has no credits/balance (used for graceful fallback).
     */
    protected function isInsufficientCredits(Response $response): bool
    {
        return $response->status() === 402;
    }

    protected function model(AIAnalysisRequest $request): string
    {
        return (string) ($request->model ?? config("services.{$this->key()}.model", '')); // @phpstan-ignore-line
    }

    /**
     * Reduce a request payload to a single prompt string.
     */
    protected function prompt(AIAnalysisRequest $request): string
    {
        $payload = $request->payload;

        if (isset($payload['text']) && is_string($payload['text'])) {
            return $payload['text'];
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * The instruction sent alongside the payload for the given operation.
     */
    protected function instruction(AIAnalysisRequest $request): string
    {
        $structured = $request->expectsStructuredOutput()
            ? "\nRespond only with valid JSON".($request->schema !== null ? ' matching this schema: '.json_encode($request->schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '.')."\nDo not wrap the JSON in markdown fences."
            : '';

        return match ($request->operation) {
            AIOperation::Summarize => 'Summarize the following news item in at most three sentences, in the same language as the content.'.$structured,
            AIOperation::ClassifyRelevance => 'Classify how relevant the following item is. Return {"relevance": <0-100 integer>, "reason": "<short reason>"}.'.$structured,
            AIOperation::ExtractStructured => 'Extract the requested structured fields from the following content.'.$structured,
            AIOperation::DetectDuplicates => 'Identify duplicate items in the following list. Return {"duplicates": [{"duplicate": "<id>", "original": "<id>"}]}.'.$structured,
            AIOperation::Analyze => 'Analyze the following content and return the key insights.'.$structured,
        };
    }

    /**
     * Decode a model content string into a structured array.
     *
     * @return array<string, mixed>
     */
    protected function decode(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*(.*?)```/s', $content, $matches) === 1) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : ['text' => $content];
    }

    /**
     * Validate a structured payload against the request schema.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function validate(array $data, AIAnalysisRequest $request): array
    {
        if ($request->schema === null) {
            return $data;
        }

        return app(StructuredOutputValidator::class)->validate($data, $request->schema, $this->key());
    }

    /**
     * Strip anything resembling an API key from a message before it is logged.
     */
    protected function sanitize(string $message): string
    {
        $patterns = [
            '/sk-ant-[A-Za-z0-9\-_]+/',
            '/sk-or-[A-Za-z0-9\-_]+/',
            '/sk-[A-Za-z0-9\-_]+/',
            '/Bearer\s+[A-Za-z0-9\-_.]+/i',
        ];

        return preg_replace($patterns, '[redacted]', $message) ?? $message;
    }
}
