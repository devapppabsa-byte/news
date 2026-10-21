<?php

namespace App\DTO;

use Carbon\CarbonInterface;

class SearchResult
{
    public function __construct(
        public readonly string $title,
        public readonly string $url,
        public readonly string $domain,
        public readonly ?string $snippet = null,
        public readonly ?CarbonInterface $publishedAt = null,
        public readonly ?CarbonInterface $collectedAt = null,
        public readonly ?string $author = null,
        public readonly ?string $sourceIdentifier = null,
    ) {}

    /**
     * Derive a domain from a URL when the provider does not supply one.
     */
    public static function domainFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if ($host === null || $host === false) {
            return '';
        }

        return preg_replace('/^www\./i', '', strtolower($host)) ?? '';
    }
}
