<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deduplication
    |--------------------------------------------------------------------------
    |
    | How news entries are deduplicated. URL hashing always runs; content
    | hashing catches the same story republished under a different URL.
    |
    */

    'content_hash' => (bool) env('NOVEDADES_CONTENT_HASH', true),

    /*
    |--------------------------------------------------------------------------
    | Source availability
    |--------------------------------------------------------------------------
    |
    | A lightweight request verifies that the original source is reachable.
    | A failure never discards the entry, it only flags it as unavailable.
    |
    */

    'source_check' => [
        'enabled' => (bool) env('NOVEDADES_SOURCE_CHECK', true),
        'method' => env('NOVEDADES_SOURCE_CHECK_METHOD', 'HEAD'),
        'timeout' => (int) env('NOVEDADES_SOURCE_CHECK_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Relevance
    |--------------------------------------------------------------------------
    |
    | Entries scored below the minimum relevance are marked as irrelevant
    | instead of being surfaced as novedades.
    |
    */

    'min_relevance' => (int) env('NOVEDADES_MIN_RELEVANCE', 30),

    /*
    |--------------------------------------------------------------------------
    | Prompt safety
    |--------------------------------------------------------------------------
    */

    'prompt_max_length' => (int) env('NOVEDADES_PROMPT_MAX_LENGTH', 2000),

    /*
    |--------------------------------------------------------------------------
    | Per-page items on the topic view
    |--------------------------------------------------------------------------
    */

    'per_page' => (int) env('NOVEDADES_PER_PAGE', 15),

];
