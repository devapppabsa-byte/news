<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use Carbon\Carbon;

class GoogleCseProvider extends AbstractWebSearchProvider
{
    public function key(): string
    {
        return 'google_cse';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.google_cse.key')))
            && filled($this->credential('cx', config('services.google_cse.cx')));
    }

    public function search(WebSearchQuery $query): array
    {
        $response = $this->run(fn () => $this->http($query)
            ->get('https://www.googleapis.com/customsearch/v1', array_filter([
                'key' => $this->credential('key', config('services.google_cse.key')),
                'cx' => $this->credential('cx', config('services.google_cse.cx')),
                'q' => $this->query($query),
                'num' => min($query->limit, 10),
                'hl' => $query->language,
                'sort' => $query->since ? 'date:r:'.$query->since->format('Ymd').':'.now()->format('Ymd') : null,
            ], fn ($value) => $value !== null && $value !== '')));

        $this->ensureSuccessful($response);

        $exclude = $this->terms(...$query->excludeTerms());
        $results = [];

        foreach ($response->json('items', []) as $item) {
            $url = $item['link'] ?? '';
            $title = $item['title'] ?? '';
            $snippet = $item['snippet'] ?? '';

            if ($url === '' || $this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $results[] = new SearchResult(
                title: trim($title),
                url: $url,
                domain: SearchResult::domainFromUrl($url) ?: ($item['displayLink'] ?? ''),
                snippet: $snippet !== '' ? trim($snippet) : null,
                publishedAt: $this->publishDate($item),
                collectedAt: now(),
                author: null,
                sourceIdentifier: $this->key(),
            );
        }

        return array_slice($results, 0, $query->limit);
    }

    protected function query(WebSearchQuery $query): string
    {
        return $query->queryString();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function publishDate(array $item): ?Carbon
    {
        $value = $item['pagemap']['metatags'][0]['article:published_time'] ?? null;

        if (! is_string($value)) {
            return null;
        }

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
