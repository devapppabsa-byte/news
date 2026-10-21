<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom interval limits
    |--------------------------------------------------------------------------
    |
    | Bounds (in minutes) that administrators allow for user defined custom
    | search frequencies. A custom interval must fall between these values.
    |
    */

    'custom_interval' => [
        'min_minutes' => (int) env('MONITORING_MIN_INTERVAL_MINUTES', 15),
        'max_minutes' => (int) env('MONITORING_MAX_INTERVAL_MINUTES', 60 * 24 * 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed search frequencies
    |--------------------------------------------------------------------------
    |
    | The frequencies users may pick from. Remove any value to prevent users
    | from choosing it.
    |
    */

    'frequencies' => [
        'hourly',
        'daily',
        'weekly',
        'manual',
        'custom',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default relevance preferences
    |--------------------------------------------------------------------------
    |
    | Applied when a monitored topic does not define its own preferences.
    |
    */

    'relevance' => [
        'min_relevance' => 0,
        'sort' => 'date',
        'notify_threshold' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'runs_per_page' => 15,

    /*
    |--------------------------------------------------------------------------
    | Queue and retries
    |--------------------------------------------------------------------------
    |
    | Scheduled and manual searches are dispatched to a queue instead of being
    | executed inside the scheduler. Retries use a progressive backoff and a
    | hard attempt limit. A worker must be running for jobs to be processed.
    |
    */

    'queue' => [
        'connection' => env('MONITORING_QUEUE_CONNECTION'),
        'name' => env('MONITORING_QUEUE', 'searches'),
        'tries' => (int) env('MONITORING_QUEUE_TRIES', 3),
        'backoff' => [60, 300, 900],
        'unique_for' => (int) env('MONITORING_QUEUE_UNIQUE_FOR', 3600),
        'timeout' => (int) env('MONITORING_QUEUE_TIMEOUT', 900),
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrency limits
    |--------------------------------------------------------------------------
    |
    | Upper bounds on how many searches may run at the same time, globally and
    | per user. Jobs that exceed a limit are released back onto the queue.
    |
    */

    'concurrency' => [
        'max_global' => (int) env('MONITORING_MAX_CONCURRENT_GLOBAL', 5),
        'max_per_user' => (int) env('MONITORING_MAX_CONCURRENT_PER_USER', 2),
        'release_after' => (int) env('MONITORING_CONCURRENCY_RELEASE_AFTER', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Estimated costs
    |--------------------------------------------------------------------------
    |
    | Rough rates used to estimate the cost of a run. These are estimates only
    | and do not reflect the real billing of any provider.
    |
    */

    'costs' => [
        'per_result' => (float) env('MONITORING_COST_PER_RESULT', 0.0),
        'per_1k_tokens_ai' => (float) env('MONITORING_COST_PER_1K_TOKENS_AI', 0.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics window
    |--------------------------------------------------------------------------
    |
    | Number of days used as the window for dashboard and monitoring averages.
    |
    */

    'metrics' => [
        'window_days' => (int) env('MONITORING_METRICS_WINDOW_DAYS', 30),
    ],

];
