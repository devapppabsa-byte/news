<?php

namespace App\Services\Search\Providers;

use App\Contracts\WebSearchProvider;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use App\Exceptions\SearchProviderException;
use App\Services\Search\CredentialRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class AbstractWebSearchProvider implements WebSearchProvider
{
    /**
     * Filter capabilities supported by the provider.
     *
     * @return array<int, SearchCapability>
     */
    protected function defaultCapabilities(): array
    {
        return [];
    }

    public function supports(SearchCapability $capability): bool
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
     * Build a configured HTTP client honouring the query timeouts/retries.
     */
    protected function http(WebSearchQuery $query): PendingRequest
    {
        return Http::timeout($query->timeout)
            ->connectTimeout($query->timeout)
            ->retry($query->retries, config('search.retry_backoff_ms'), throw: false)
            ->withHeaders([
                'User-Agent' => config('search.user_agent'),
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
            throw new SearchProviderException($this->key(), 'Connection failed: '.$exception->getMessage());
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

        throw new SearchProviderException(
            $this->key(),
            "Request failed with status {$response->status()}",
        );
    }

    /**
     * Lowercase, trimmed keyword list.
     *
     * @return array<int, string>
     */
    protected function terms(string ...$values): array
    {
        return array_values(array_filter(array_map(
            fn (string $value): string => strtolower(trim($value)),
            $values,
        )));
    }

    /**
     * Whether the text mentions any excluded keyword.
     *
     * @param  array<int, string>  $exclude
     */
    protected function containsExcluded(string $text, array $exclude): bool
    {
        $text = strtolower($text);

        foreach ($exclude as $keyword) {
            if ($keyword !== '' && str_contains($text, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether the domain is blocked by the excluded domain list.
     *
     * @param  array<int, string>  $excludedDomains
     */
    protected function domainExcluded(string $domain, array $excludedDomains): bool
    {
        foreach ($excludedDomains as $excluded) {
            $excluded = strtolower(trim($excluded));

            if ($excluded !== '' && (str_ends_with($domain, $excluded) || $domain === $excluded)) {
                return true;
            }
        }

        return false;
    }
}
