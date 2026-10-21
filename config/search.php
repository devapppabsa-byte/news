<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active providers
    |--------------------------------------------------------------------------
    |
    | The search providers that run for every monitored topic. Providers
    | without the required credentials are skipped automatically at runtime.
    |
    */

    'providers' => array_filter(array_map('trim', explode(',', (string) env(
        'SEARCH_PROVIDERS',
        'rss,brave,tavily,google_cse,duckduckgo,openrouter',
    )))),

    /*
    |--------------------------------------------------------------------------
    | Result limits and HTTP behaviour
    |--------------------------------------------------------------------------
    */

    'max_results' => (int) env('SEARCH_MAX_RESULTS', 10),
    'timeout' => (int) env('SEARCH_TIMEOUT', 10),
    'retries' => (int) env('SEARCH_RETRIES', 2),
    'retry_backoff_ms' => (int) env('SEARCH_RETRY_BACKOFF_MS', 200),

    /*
    |--------------------------------------------------------------------------
    | RSS feeds
    |--------------------------------------------------------------------------
    |
    | Default feeds used by the RSS provider when a topic has no preferred
    | domains. Each entry must be a valid feed URL.
    |
    */

    'rss' => [
        'feeds' => array_filter(array_map('trim', explode(',', (string) env(
            'SEARCH_RSS_FEEDS',
            'https://feeds.bbci.co.uk/news/rss.xml,https://www.rtve.es/api/noticias.rss',
        )))),
        'timeout' => (int) env('SEARCH_RSS_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | User agent
    |--------------------------------------------------------------------------
    |
    | Sent with every outbound request so providers can identify the client.
    |
    */

    'user_agent' => env('SEARCH_USER_AGENT', 'NewsMonitor/1.0 (+https://example.com)'),

];
