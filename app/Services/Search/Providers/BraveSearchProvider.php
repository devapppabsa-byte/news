<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use Carbon\Carbon;

class BraveSearchProvider extends AbstractWebSearchProvider
{
    public function key(): string
    {
        return 'brave';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.brave.key')));
    }

    public function search(WebSearchQuery $query): array
    {
        $response = $this->run(fn () => $this->http($query)
            ->withToken((string) $this->credential('key', config('services.brave.key')))
            ->withHeaders(['Accept' => 'application/json'])
            ->get('https://api.search.brave.com/res/v1/web/search', [
                'q' => $query->queryString(),
                'count' => $query->limit,
                'country' => $query->country,
                'search_lang' => $query->language,
                'freshness' => $this->freshness($query),
            ]));

        $this->ensureSuccessful($response);

        $exclude = $this->terms(...$query->excludeTerms());
        $results = [];

        foreach ($response->json('web.results', []) as $item) {
            $url = $item['url'] ?? '';
            $title = $item['title'] ?? '';
            $snippet = $item['description'] ?? '';

            if ($url === '' || $this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $results[] = new SearchResult(
                title: trim(strip_tags($title)),
                url: $url,
                domain: SearchResult::domainFromUrl($url),
                snippet: $snippet !== '' ? trim(strip_tags($snippet)) : null,
                publishedAt: isset($item['page_age']) ? $this->parseDate($item['page_age']) : null,
                collectedAt: now(),
                author: null,
                sourceIdentifier: $this->key(),
            );
        }

        return array_slice($results, 0, $query->limit);
    }

    protected function freshness(WebSearchQuery $query): ?string
    {
        if ($query->since === null) {
            return null;
        }

        return match (true) {
            $query->since->greaterThan(now()->subDay()) => 'pd',
            $query->since->greaterThan(now()->subWeek()) => 'pw',
            $query->since->greaterThan(now()->subMonth()) => 'pm',
            default => 'py',
        };
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
            SearchCapability::DateFilter,
            SearchCapability::LanguageFilter,
            SearchCapability::KeywordFilter,
        ];
    }
}
