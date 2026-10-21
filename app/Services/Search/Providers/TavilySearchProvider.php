<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use Carbon\Carbon;

class TavilySearchProvider extends AbstractWebSearchProvider
{
    public function key(): string
    {
        return 'tavily';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.tavily.key')));
    }

    public function search(WebSearchQuery $query): array
    {
        $response = $this->run(fn () => $this->http($query)
            ->withToken((string) $this->credential('key', config('services.tavily.key')))
            ->post('https://api.tavily.com/search', array_filter([
                'query' => $query->queryString(),
                'max_results' => $query->limit,
                'search_depth' => 'basic',
                'include_domains' => $query->preferredDomains,
                'exclude_domains' => $query->excludedDomains,
            ], fn ($value) => $value !== null && $value !== [])));

        $this->ensureSuccessful($response);

        $exclude = $this->terms(...$query->excludeTerms());
        $results = [];

        foreach ($response->json('results', []) as $item) {
            $url = $item['url'] ?? '';
            $title = $item['title'] ?? '';
            $snippet = $item['content'] ?? '';

            if ($url === '' || $this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $results[] = new SearchResult(
                title: trim($title),
                url: $url,
                domain: SearchResult::domainFromUrl($url),
                snippet: $snippet !== '' ? trim($snippet) : null,
                publishedAt: isset($item['published_date']) ? $this->parseDate($item['published_date']) : null,
                collectedAt: now(),
                author: null,
                sourceIdentifier: $this->key(),
            );
        }

        return array_slice($results, 0, $query->limit);
    }

    protected function parseDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, SearchCapability>
     */
    protected function defaultCapabilities(): array
    {
        return [
            SearchCapability::DomainFilter,
            SearchCapability::KeywordFilter,
        ];
    }
}
