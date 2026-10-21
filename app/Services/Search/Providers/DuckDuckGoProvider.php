<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;

class DuckDuckGoProvider extends AbstractWebSearchProvider
{
    protected const ENDPOINT = 'https://html.duckduckgo.com/html/';

    public function key(): string
    {
        return 'duckduckgo';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function search(WebSearchQuery $query): array
    {
        $response = $this->run(fn () => $this->http($query)
            ->withHeaders(['Accept' => 'text/html'])
            ->asForm()
            ->post(static::ENDPOINT, ['q' => $query->queryString()]));

        $this->ensureSuccessful($response);

        return $this->parse($response->body(), $query);
    }

    /**
     * @return array<int, SearchResult>
     */
    protected function parse(string $html, WebSearchQuery $query): array
    {
        $docs = new \DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $docs->loadHTML($html);
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($docs);
        $exclude = $this->terms(...$query->excludeTerms());
        $results = [];

        foreach ($xpath->query('//a[contains(@class, "result__a")]') as $node) {
            /** @var \DOMElement $node */
            $url = $this->resolveUrl($node->getAttribute('href'));
            $title = trim($node->textContent);

            if ($url === '' || $title === '') {
                continue;
            }

            $snippetNode = $xpath->query('.//ancestor::div[contains(@class,"result")]//a[contains(@class,"result__snippet")]', $node)->item(0);
            $snippet = $snippetNode ? trim($snippetNode->textContent) : null;

            if ($this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $domain = SearchResult::domainFromUrl($url);

            if ($this->domainExcluded($domain, $query->excludedDomains)) {
                continue;
            }

            $results[] = new SearchResult(
                title: html_entity_decode($title),
                url: $url,
                domain: $domain,
                snippet: $snippet !== null && $snippet !== '' ? html_entity_decode($snippet) : null,
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

    protected function resolveUrl(string $href): string
    {
        if ($href === '') {
            return '';
        }

        $parts = parse_url($href);
        parse_str($parts['query'] ?? '', $query);

        if (isset($query['uddg'])) {
            return (string) $query['uddg'];
        }

        return str_starts_with($href, '//') ? "https:$href" : $href;
    }

    /**
     * @return array<int, SearchCapability>
     */
    protected function defaultCapabilities(): array
    {
        return [
            SearchCapability::KeywordFilter,
        ];
    }
}
