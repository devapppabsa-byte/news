<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active AI providers
    |--------------------------------------------------------------------------
    |
    | The AI providers that may be selected. Providers without the required
    | credentials are skipped automatically at runtime.
    |
    */

    'providers' => array_filter(array_map('trim', explode(',', (string) env(
        'AI_PROVIDERS',
        'openai,openrouter,anthropic',
    )))),

    /*
    |--------------------------------------------------------------------------
    | Default provider configuration
    |--------------------------------------------------------------------------
    |
    | Used when no active row exists in the ai_settings table. The admin panel
    | writes to that table; these values are the fallback so the application
    | works out of the box.
    |
    */

    'provider' => env('AI_PROVIDER', 'openai'),
    'model' => env('AI_MODEL', 'gpt-4o-mini'),
    'search_mode' => env('AI_SEARCH_MODE', 'external'),
    'parameters' => [],
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1024),
    'timeout' => (int) env('AI_TIMEOUT', 30),
    'retries' => (int) env('AI_RETRIES', 2),
    'retry_backoff_ms' => (int) env('AI_RETRY_BACKOFF_MS', 200),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER'),
    'fallback_model' => env('AI_FALLBACK_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Pipeline behaviour
    |--------------------------------------------------------------------------
    |
    | Whether AI enrichment runs after each monitored topic search.
    |
    */

    'analyze_after_search' => (bool) env('AI_ANALYZE_AFTER_SEARCH', true),

    /*
    |--------------------------------------------------------------------------
    | User agent
    |--------------------------------------------------------------------------
    */

    'user_agent' => env('AI_USER_AGENT', 'NewsMonitor/1.0 (+https://example.com)'),

];
