<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | Web push requires valid VAPID keys. When disabled, no push job is queued
    | and the subscription endpoints stop accepting registrations.
    |
    */

    'enabled' => (bool) env('WEBPUSH_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | VAPID credentials
    |--------------------------------------------------------------------------
    |
    | Generate a keypair with `php artisan webpush:vapid`. The public key is
    | shared with the browser, the private key stays on the server. The subject
    | must be a contact URI (a mailto: address is recommended).
    |
    */

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:hello@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payload privacy
    |--------------------------------------------------------------------------
    |
    | Notifications may be displayed on a locked screen. When "privacy_mode" is
    | on, the payload only contains a generic message and the deep link, never
    | the headline or summary. A user may opt out of privacy mode individually.
    |
    */

    'privacy_mode' => (bool) env('WEBPUSH_PRIVACY_MODE', true),

    'generic_title' => env('WEBPUSH_GENERIC_TITLE', 'New novedad'),

    'generic_body' => env('WEBPUSH_GENERIC_BODY', 'You have a new novedad waiting.'),

    /*
    |--------------------------------------------------------------------------
    | Payload limits
    |--------------------------------------------------------------------------
    |
    | The encrypted push payload is capped by the push services. Keep the text
    | short so it never exceeds the transport limit.
    |
    */

    'max_title_length' => (int) env('WEBPUSH_MAX_TITLE_LENGTH', 120),

    'max_body_length' => (int) env('WEBPUSH_MAX_BODY_LENGTH', 240),

    /*
    |--------------------------------------------------------------------------
    | Notification behaviour
    |--------------------------------------------------------------------------
    */

    'ttl' => (int) env('WEBPUSH_TTL', 3600),

    'urgency' => env('WEBPUSH_URGENCY', 'normal'),

    /*
    |--------------------------------------------------------------------------
    | Queue and retries
    |--------------------------------------------------------------------------
    |
    | Push deliveries reuse the notifications queue and its retry policy.
    |
    */

    'queue' => [
        'connection' => env('NOTIFICATIONS_QUEUE_CONNECTION'),
        'name' => env('WEBPUSH_QUEUE', env('NOTIFICATIONS_QUEUE', 'notifications')),
        'tries' => (int) env('WEBPUSH_QUEUE_TRIES', 3),
        'backoff' => [60, 300, 900],
        'unique_for' => (int) env('WEBPUSH_QUEUE_UNIQUE_FOR', 3600),
        'timeout' => (int) env('WEBPUSH_QUEUE_TIMEOUT', 120),
    ],

];
