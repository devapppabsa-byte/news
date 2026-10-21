<?php

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Exceptions\SearchProviderException;
use App\Services\Search\Providers\BraveSearchProvider;
use App\Services\Search\Providers\DuckDuckGoProvider;
use App\Services\Search\Providers\OpenRouterSearchProvider;

test('a real duckduckgo search returns normalized results', function () {
    try {
        $results = (new DuckDuckGoProvider)->search(new WebSearchQuery(
            term: 'laravel framework',
            limit: 5,
            timeout: 15,
            retries: 1,
        ));
    } catch (SearchProviderException $e) {
        $this->markTestSkipped('DuckDuckGo blocked this server (anti-bot): '.$e->getMessage());
    }

    expect($results)->toBeArray();

    foreach ($results as $result) {
        expect($result)->toBeInstanceOf(SearchResult::class)
            ->and($result->url)->toStartWith('http')
            ->and($result->domain)->not->toBe('');
    }
})->group('integration');

test('a real brave search returns normalized results when a key is configured', function () {
    if (blank(config('services.brave.key'))) {
        $this->markTestSkipped('BRAVE_SEARCH_API_KEY is not configured.');
    }

    $results = (new BraveSearchProvider)->search(new WebSearchQuery(
        term: 'laravel framework',
        limit: 5,
        timeout: 15,
        retries: 1,
    ));

    expect($results)->toBeArray();
})->group('integration');

test('a real openrouter online search returns results without exposing the key', function () {
    if (blank(config('services.openrouter.key'))) {
        $this->markTestSkipped('OPENROUTER_API_KEY is not configured.');
    }

    try {
        $results = (new OpenRouterSearchProvider)->search(new WebSearchQuery(
            term: 'latest laravel news',
            limit: 5,
            timeout: 120,
            retries: 0,
        ));
    } catch (SearchProviderException $e) {
        if (str_contains($e->getMessage(), 'status 402')) {
            $this->markTestSkipped('OpenRouter account has insufficient credits (402).');
        }

        throw $e;
    }

    expect($results)->toBeArray();

    foreach ($results as $result) {
        expect($result->url)->toStartWith('http')
            ->and($result->url)->not->toContain('sk-or-v1');
    }
})->group('integration');
