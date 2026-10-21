<?php

namespace App\Support;

use Carbon\Carbon;

class NotificationKey
{
    /**
     * A stable, unique key for an individual delivery. Two identical alerts for
     * the same user, entry and channel collapse into one.
     */
    public static function individual(int $userId, int $newsEntryId, string $channel = 'mail'): string
    {
        return hash('sha256', implode('|', [
            'individual',
            $userId,
            $newsEntryId,
            $channel,
        ]));
    }

    /**
     * A stable key for a digest covering a user for a given period. The period
     * granularity keeps a user from receiving more than one digest per window.
     */
    public static function digest(int $userId, string $period, string $channel = 'mail'): string
    {
        return hash('sha256', implode('|', [
            'digest',
            $userId,
            $period,
            $channel,
        ]));
    }

    /**
     * Render a period string (for example "2026-10-21") for a digest window.
     */
    public static function periodFor(?\DateTimeInterface $date = null): string
    {
        $date ??= now();

        return Carbon::instance($date)->toDateString();
    }
}
