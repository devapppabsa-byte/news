<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class OpenRouterSearchProvider extends AbstractWebSearchProvider
{
    protected const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function key(): string
    {
        return 'openrouter';
    }

    public function isConfigured(): bool
    {
        return filled($this->credential('key', config('services.openrouter.key')));
    }

    public function search(WebSearchQuery $query): array
    {
        $response = $this->run(fn () => $this->http($query)
            ->withToken((string) $this->credential('key', config('services.openrouter.key')))
            ->withHeaders([
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])
            ->post(static::ENDPOINT, [
                'model' => config('services.openrouter.model', 'openrouter/auto:online'),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $this->prompt($query),
                    ],
                ],
                'plugins' => [
                    ['id' => 'web'],
                ],
            ]));

        $this->ensureSuccessful($response);

        return $this->extractCitations($response->json() ?? [], $query);
    }

    protected function prompt(WebSearchQuery $query): string
    {
        $prompt = "Search the web and return the most relevant news results about: {$query->queryString()}.";

        if ($query->excludeTerms() !== []) {
            $prompt .= ' Exclude any result mentioning: '.implode(', ', $query->excludeTerms()).'.';
        }

        if ($query->language !== null) {
            $prompt .= " Prefer content written in the locale [{$query->language}].";
        }

        return $prompt;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, SearchResult>
     */
    protected function extractCitations(array $payload, WebSearchQuery $query): array
    {
        $message = Arr::get($payload, 'choices.0.message', []);
        $annotations = Arr::get($message, 'annotations', []);
        $exclude = $this->terms(...$query->excludeTerms());
        $results = [];

        foreach ($annotations as $annotation) {
            $citation = Arr::get($annotation, 'url_citation');

            if ($citation === null) {
                continue;
            }

            $url = $citation['url'] ?? '';

            if ($url === '') {
                continue;
            }

            $title = $citation['title'] ?? $url;
            $snippet = $citation['content'] ?? null;

            if ($this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $domain = SearchResult::domainFromUrl($url);

            if ($this->domainExcluded($domain, $query->excludedDomains)) {
                continue;
            }

            $results[] = new SearchResult(
                title: Str::limit(trim($title), 250, ''),
                url: $url,
                domain: $domain,
                snippet: $snippet !== null && $snippet !== '' ? Str::limit(trim($snippet), 500) : null,
                publishedAt: null,
                collectedAt: now(),
                author: null,
                sourceIdentifier: $this->key(),
            );

            if (count($results) >= $query->limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @return array<int, SearchCapability>
     */
    protected function defaultCapabilities(): array
    {
        return [
            SearchCapability::LanguageFilter,
            SearchCapability::KeywordFilter,
        ];
    }
}
