<?php

use App\DTO\SearchResult;
use App\DTO\WebSearchQuery;
use App\Exceptions\SearchProviderException;
use App\Services\Search\Providers\BraveSearchProvider;
use App\Services\Search\Providers\DuckDuckGoProvider;
use App\Services\Search\Providers\GoogleCseProvider;
use App\Services\Search\Providers\OpenRouterSearchProvider;
use App\Services\Search\Providers\RssFeedProvider;
use App\Services\Search\Providers\TavilySearchProvider;

function query(array $overrides = []): WebSearchQuery
{
    return new WebSearchQuery(...array_merge([
        'term' => 'climate change',
        'limit' => 10,
        'timeout' => 5,
        'retries' => 1,
    ], $overrides));
}

test('rss provider parses an rss feed into normalized results', function () {
    Http::fake([
        'feeds.example.com/*' => Http::response(<<<'XML'
        <?xml version="1.0"?>
        <rss version="2.0"><channel>
            <item>
                <title>Climate report released</title>
                <link>https://www.reuters.com/climate/report</link>
                <description><![CDATA[<p>A <b>new</b> report</p>]]></description>
                <pubDate>Mon, 01 Jan 2024 10:00:00 GMT</pubDate>
                <author>Jane Doe</author>
            </item>
        </channel></rss>
        XML),
    ]);
    config()->set('search.rss.feeds', ['https://feeds.example.com/rss']);

    $results = (new RssFeedProvider)->search(query());

    expect($results)->toHaveCount(1)
        ->and($results[0])->toBeInstanceOf(SearchResult::class)
        ->and($results[0]->title)->toBe('Climate report released')
        ->and($results[0]->url)->toBe('https://www.reuters.com/climate/report')
        ->and($results[0]->domain)->toBe('reuters.com')
        ->and($results[0]->snippet)->toBe('A new report')
        ->and($results[0]->sourceIdentifier)->toBe('rss')
        ->and($results[0]->publishedAt)->not->toBeNull();
});

test('rss provider filters out excluded keywords', function () {
    Http::fake([
        'feeds.example.com/*' => Http::response(<<<'XML'
        <?xml version="1.0"?>
        <rss version="2.0"><channel>
            <item>
                <title>Celebrity gossip about climate</title>
                <link>https://tabloid.com/gossip</link>
                <description>gossip</description>
            </item>
            <item>
                <title>Real climate study</title>
                <link>https://science.org/study</link>
            </item>
        </channel></rss>
        XML),
    ]);
    config()->set('search.rss.feeds', ['https://feeds.example.com/rss']);

    $results = (new RssFeedProvider)
        ->search(query(['excludeKeywords' => ['gossip']]));

    expect($results)->toHaveCount(1)
        ->and($results[0]->title)->toBe('Real climate study');
});

test('brave provider normalizes its json response', function () {
    Http::fake([
        'api.search.brave.com/*' => Http::response([
            'web' => [
                'results' => [[
                    'title' => 'Brave result',
                    'url' => 'https://example.com/a',
                    'description' => 'A snippet',
                    'page_age' => '2024-01-01T00:00:00Z',
                ]],
            ],
        ]),
    ]);
    config()->set('services.brave.key', 'brave-key');

    $provider = new BraveSearchProvider;
    $results = $provider->search(query());

    expect($results)->toHaveCount(1)
        ->and($results[0]->domain)->toBe('example.com')
        ->and($results[0]->sourceIdentifier)->toBe('brave')
        ->and($results[0]->publishedAt)->not->toBeNull();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer brave-key'));
});

test('tavily provider sends domains and normalizes results', function () {
    Http::fake([
        'api.tavily.com/*' => Http::response([
            'results' => [[
                'title' => 'Tavily result',
                'url' => 'https://news.org/x',
                'content' => 'Content here',
                'published_date' => '2024-02-02',
            ]],
        ]),
    ]);
    config()->set('services.tavily.key', 'tavily-key');

    $results = (new TavilySearchProvider)
        ->search(query(['preferredDomains' => ['news.org'], 'excludedDomains' => ['bad.com']]));

    expect($results)->toHaveCount(1)
        ->and($results[0]->domain)->toBe('news.org');

    Http::assertSent(fn ($request) => $request['include_domains'] === ['news.org']
        && $request['exclude_domains'] === ['bad.com']);
});

test('google cse provider normalizes results', function () {
    Http::fake([
        'www.googleapis.com/*' => Http::response([
            'items' => [[
                'title' => 'CSE result',
                'link' => 'https://blog.example.com/post',
                'snippet' => 'Snippet',
                'displayLink' => 'blog.example.com',
            ]],
        ]),
    ]);
    config()->set('services.google_cse.key', 'cse-key');
    config()->set('services.google_cse.cx', 'cse-cx');

    $results = (new GoogleCseProvider)->search(query());

    expect($results)->toHaveCount(1)
        ->and($results[0]->domain)->toBe('blog.example.com')
        ->and($results[0]->sourceIdentifier)->toBe('google_cse');
});

test('duckduckgo provider parses html search results', function () {
    Http::fake([
        'html.duckduckgo.com/*' => Http::response(<<<'HTML'
        <html><body>
            <div class="result">
                <a class="result__a" href="//duckduckgo.com/l/?uddg=https%3A%2F%2Fexample.com%2Fstory">DDG Story</a>
                <a class="result__snippet">A snippet here</a>
            </div>
            <div class="result">
                <a class="result__a" href="https://other.com/page">Other Page</a>
            </div>
        </body></html>
        HTML),
    ]);

    $results = (new DuckDuckGoProvider)->search(query());

    expect($results)->toHaveCount(2)
        ->and($results[0]->url)->toBe('https://example.com/story')
        ->and($results[0]->domain)->toBe('example.com')
        ->and($results[0]->sourceIdentifier)->toBe('duckduckgo');
});

test('openrouter provider extracts citations from annotations', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [[
                'message' => [
                    'content' => 'Here is a summary.',
                    'annotations' => [[
                        'type' => 'url_citation',
                        'url_citation' => [
                            'url' => 'https://source.com/article',
                            'title' => 'Source article',
                            'content' => 'Excerpt text',
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);
    config()->set('services.openrouter.key', 'or-key');

    $results = (new OpenRouterSearchProvider)->search(query());

    expect($results)->toHaveCount(1)
        ->and($results[0]->url)->toBe('https://source.com/article')
        ->and($results[0]->domain)->toBe('source.com')
        ->and($results[0]->sourceIdentifier)->toBe('openrouter');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer or-key'));
});

test('a provider http failure raises a search provider exception', function () {
    Http::fake([
        'api.search.brave.com/*' => Http::response('server error', 500),
    ]);
    config()->set('services.brave.key', 'brave-key');

    expect(fn () => (new BraveSearchProvider)->search(query()))
        ->toThrow(SearchProviderException::class);
});
