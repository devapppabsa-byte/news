<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When disabled no notification is ever dispatched. The per-user and
    | per-topic flags still apply on top of this global switch.
    |
    */

    'enabled' => (bool) env('NOTIFICATIONS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Delivery mode
    |--------------------------------------------------------------------------
    |
    | "digest" batches novedades into a periodic summary. "individual" sends an
    | email as soon as a novedad is discovered. "both" honours the per-topic
    | preference. Defaults are used when a topic does not define its own.
    |
    */

    'default_mode' => env('NOTIFICATIONS_DEFAULT_MODE', 'digest'),

    'modes' => [
        'digest',
        'individual',
        'both',
        'none',
    ],

    /*
    |--------------------------------------------------------------------------
    | Digest schedule
    |--------------------------------------------------------------------------
    |
    | How often the digest job runs. The daily time is the hour (0-23) at which
    | the summary is prepared.
    |
    */

    'digest' => [
        'frequency' => env('NOTIFICATIONS_DIGEST_FREQUENCY', 'daily'),
        'at' => (int) env('NOTIFICATIONS_DIGEST_AT', 8),
        'max_items' => (int) env('NOTIFICATIONS_DIGEST_MAX_ITEMS', 25),
    ],

    /*
    |--------------------------------------------------------------------------
    | Limits
    |--------------------------------------------------------------------------
    |
    | Per-user throttles applied at dispatch time. The daily cap protects users
    | from a burst of individual alerts.
    |
    */

    'limits' => [
        'max_per_day' => (int) env('NOTIFICATIONS_MAX_PER_DAY', 20),
        'max_per_user_digest_items' => (int) env('NOTIFICATIONS_MAX_DIGEST_ITEMS', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue and retries
    |--------------------------------------------------------------------------
    |
    | Notification jobs run on a dedicated queue so they never compete with
    | searches. A worker must be running for jobs to be processed.
    |
    */

    'queue' => [
        'connection' => env('NOTIFICATIONS_QUEUE_CONNECTION'),
        'name' => env('NOTIFICATIONS_QUEUE', 'notifications'),
        'tries' => (int) env('NOTIFICATIONS_QUEUE_TRIES', 3),
        'backoff' => [60, 300, 900],
        'unique_for' => (int) env('NOTIFICATIONS_QUEUE_UNIQUE_FOR', 3600),
        'timeout' => (int) env('NOTIFICATIONS_QUEUE_TIMEOUT', 120),
    ],

    /*
    |--------------------------------------------------------------------------
    | Minimum relevance
    |--------------------------------------------------------------------------
    |
    | A novedad is only eligible for notification when its relevance score is
    | at or above this value, unless the topic defines a stricter threshold.
    |
    */

    'min_relevance' => (int) env('NOTIFICATIONS_MIN_RELEVANCE', 0),

];
