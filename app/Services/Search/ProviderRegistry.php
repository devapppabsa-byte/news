<?php

namespace App\Services\Search;

use App\Contracts\WebSearchProvider;
use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Exceptions\SearchProviderException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProviderRegistry
{
    /**
     * @param  iterable<WebSearchProvider>  $providers
     */
    public function __construct(
        protected iterable $providers,
        protected CredentialRepository $credentials,
    ) {}

    /**
     * The providers that are active in config and fully configured.
     *
     * @return Collection<int, WebSearchProvider>
     */
    public function enabled(): Collection
    {
        $active = config('search.providers', []);

        return collect($this->providers)
            ->filter(fn (WebSearchProvider $provider): bool => in_array($provider->key(), $active, true))
            ->filter(fn (WebSearchProvider $provider): bool => $provider->isConfigured())
            ->values();
    }

    /**
     * Run every enabled provider and merge/deduplicate their results.
     *
     * @return array{results: array<int, SearchResult>, errors: array<int, string>}
     */
    public function search(WebSearchQuery $query): array
    {
        $results = [];
        $errors = [];
        $seen = [];

        foreach ($this->enabled() as $provider) {
            try {
                $providerResults = $provider->search($query);
            } catch (SearchProviderException $exception) {
                $errors[] = $exception->getMessage();
                Log::warning('Search provider failed', [
                    'provider' => $provider->key(),
                    'message' => $exception->getMessage(),
                ]);

                continue;
            }

            foreach ($providerResults as $result) {
                $key = $this->dedupeKey($result);

                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $results[] = $result;
            }
        }

        return [
            'results' => array_slice($results, 0, $query->limit * max(count($this->enabled()), 1)),
            'errors' => $errors,
        ];
    }

    protected function dedupeKey(SearchResult $result): string
    {
        $url = rtrim(strtolower($result->url), '/');

        return $url !== ''
            ? $url
            : strtolower($result->domain.'|'.$result->title);
    }
}
