<?php

namespace App\Services\Search\Providers;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Exceptions\SearchProviderException;
use Carbon\Carbon;

class RssFeedProvider extends AbstractWebSearchProvider
{
    public function key(): string
    {
        return 'rss';
    }

    public function isConfigured(): bool
    {
        return $this->feeds() !== [];
    }

    public function search(WebSearchQuery $query): array
    {
        $results = [];
        $exclude = $this->terms(...$query->excludeTerms());

        foreach ($this->feeds() as $feed) {
            $response = $this->run(fn () => $this->http($query)
                ->withHeaders(['Accept' => 'application/rss+xml, application/xml, text/xml'])
                ->get($feed));

            $this->ensureSuccessful($response);

            $results = array_merge($results, $this->parse($response->body(), $feed, $query, $exclude));
        }

        return array_slice($results, 0, $query->limit);
    }

    /**
     * @param  array<int, string>  $exclude
     * @return array<int, SearchResult>
     */
    protected function parse(string $body, string $feed, WebSearchQuery $query, array $exclude): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new SearchProviderException($this->key(), "Invalid feed XML from $feed");
        }

        $items = $this->items($xml);
        $results = [];

        foreach ($items as $item) {
            $url = $this->text($item, 'link');

            if ($url === '') {
                continue;
            }

            $title = $this->text($item, 'title');
            $snippet = $this->text($item, 'description') ?: $this->text($item, 'summary');
            $snippet = trim(html_entity_decode(strip_tags($snippet)));

            if ($this->containsExcluded($title.' '.$snippet, $exclude)) {
                continue;
            }

            $domain = SearchResult::domainFromUrl($url);

            if ($this->domainExcluded($domain, $query->excludedDomains)) {
                continue;
            }

            if ($query->preferredDomains !== [] && ! $this->matchesPreferred($domain, $query->preferredDomains)) {
                continue;
            }

            $results[] = new SearchResult(
                title: $title !== '' ? $title : $domain,
                url: $url,
                domain: $domain,
                snippet: $snippet !== '' ? $snippet : null,
                publishedAt: $this->date($item),
                collectedAt: now(),
                author: $this->text($item, 'author') ?: null,
                sourceIdentifier: $this->key(),
            );
        }

        return $results;
    }

    /**
     * @return array<int, \SimpleXMLElement>
     */
    protected function items(\SimpleXMLElement $xml): array
    {
        if (isset($xml->channel->item)) {
            return iterator_to_array($xml->channel->item);
        }

        if (isset($xml->entry)) {
            return iterator_to_array($xml->entry);
        }

        return [];
    }

    protected function text(\SimpleXMLElement $item, string $name): string
    {
        $namespaces = $item->getNameSpaces(true);

        if (isset($namespaces[''])) {
            $default = $item->children($namespaces['']);
            if (isset($default->{$name})) {
                return trim((string) $default->{$name});
            }
        }

        if (isset($item->{$name})) {
            return trim((string) $item->{$name});
        }

        return '';
    }

    protected function date(\SimpleXMLElement $item): ?Carbon
    {
        foreach (['pubDate', 'published', 'updated', 'dc:date'] as $field) {
            $value = $this->text($item, $field);

            if ($value !== '') {
                try {
                    return Carbon::parse($value);
                } catch (\Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $preferred
     */
    protected function matchesPreferred(string $domain, array $preferred): bool
    {
        foreach ($preferred as $candidate) {
            $candidate = strtolower(trim($candidate));

            if ($candidate !== '' && (str_ends_with($domain, $candidate) || $domain === $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function feeds(): array
    {
        return array_values(array_filter(config('search.rss.feeds', [])));
    }
}
