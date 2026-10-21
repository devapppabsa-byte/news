<?php

namespace App\DTO;

use Carbon\CarbonInterface;

class WebSearchQuery
{
    /**
     * @param  array<int, string>  $includeKeywords
     * @param  array<int, string>  $excludeKeywords
     * @param  array<int, string>  $preferredDomains
     * @param  array<int, string>  $excludedDomains
     */
    public function __construct(
        public readonly string $term,
        public readonly array $includeKeywords = [],
        public readonly array $excludeKeywords = [],
        public readonly array $preferredDomains = [],
        public readonly array $excludedDomains = [],
        public readonly ?string $language = null,
        public readonly ?string $country = null,
        public readonly ?CarbonInterface $since = null,
        public readonly ?CarbonInterface $until = null,
        public readonly int $limit = 10,
        public readonly int $timeout = 10,
        public readonly int $retries = 2,
    ) {}

    /**
     * The full text query built from the term and the include keywords.
     */
    public function queryString(): string
    {
        $parts = array_filter([
            $this->term,
            ...$this->includeKeywords,
        ]);

        return trim(implode(' ', $parts));
    }

    /**
     * @return array<int, string>
     */
    public function excludeTerms(): array
    {
        return array_values(array_filter($this->excludeKeywords));
    }
}
