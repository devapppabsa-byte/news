<?php

namespace App\Support;

use App\Models\NewsItem;
use App\Models\Preference;
use Illuminate\Support\Str;

class WebPushPayload
{
    /**
     * Build the payload for a novedad alert. When privacy mode is on the
     * headline and summary are replaced with a generic message so nothing
     * sensitive shows on a locked screen.
     *
     * @return array<string, mixed>
     */
    public static function forNovedad(NewsItem $item, ?Preference $preference = null): array
    {
        $private = $preference?->prefersPrivatePush()
            ?? (bool) config('webpush.privacy_mode', true);

        $title = $private
            ? (string) config('webpush.generic_title', 'New novedad')
            : Str::limit($item->title, (int) config('webpush.max_title_length', 120), '');

        $body = $private
            ? (string) config('webpush.generic_body', 'You have a new novedad waiting.')
            : Str::limit(
                $item->ai_summary ?: $item->body,
                (int) config('webpush.max_body_length', 240),
                '…',
            );

        return [
            'title' => $title,
            'body' => $body,
            'url' => self::novedadUrl($item),
            'tag' => 'novedad-'.($item->news_entry_id ?? $item->id),
            'private' => $private,
        ];
    }

    /**
     * The in-app location the notification opens when clicked.
     */
    public static function novedadUrl(NewsItem $item): string
    {
        return route('topics.show', $item->topic_id).'#news-item-'.$item->id;
    }

    /**
     * A payload for the delivery test page. It always uses the generic copy and
     * points at the test page itself.
     *
     * @return array<string, mixed>
     */
    public static function forTest(): array
    {
        return [
            'title' => (string) config('webpush.generic_title', 'New novedad'),
            'body' => (string) config('webpush.generic_body', 'You have a new novedad waiting.'),
            'url' => route('push.test'),
            'tag' => 'push-test',
            'private' => true,
        ];
    }
}
