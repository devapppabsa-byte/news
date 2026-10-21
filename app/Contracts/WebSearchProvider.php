<?php

namespace App\Contracts;

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Enums\SearchCapability;
use App\Exceptions\SearchProviderException;

interface WebSearchProvider
{
    /**
     * A stable identifier for the provider (e.g. "brave", "rss").
     */
    public function key(): string;

    /**
     * Whether the provider can apply the given filter capability.
     */
    public function supports(SearchCapability $capability): bool;

    /**
     * Whether the provider is usable (e.g. required credentials present).
     */
    public function isConfigured(): bool;

    /**
     * Run a search and return normalized results.
     *
     * @return array<int, SearchResult>
     *
     * @throws SearchProviderException
     */
    public function search(WebSearchQuery $query): array;
}
