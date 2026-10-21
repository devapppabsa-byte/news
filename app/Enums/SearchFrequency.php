<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum SearchFrequency: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Manual = 'manual';
    case Custom = 'custom';

    /**
     * Whether this frequency is scheduled automatically.
     */
    public function isScheduled(): bool
    {
        return $this !== self::Manual;
    }

    /**
     * Interval in minutes for the predefined frequencies, or null when the
     * frequency depends on a user supplied custom interval.
     */
    public function intervalMinutes(): ?int
    {
        return match ($this) {
            self::Hourly => 60,
            self::Daily => 60 * 24,
            self::Weekly => 60 * 24 * 7,
            self::Manual, self::Custom => null,
        };
    }

    /**
     * Calculate the next run date from the given moment.
     */
    public function nextRunFrom(
        CarbonInterface $from,
        ?int $customIntervalMinutes = null,
    ): ?CarbonInterface {
        $minutes = $this === self::Custom
            ? $customIntervalMinutes
            : $this->intervalMinutes();

        return $minutes === null ? null : $from->copy()->addMinutes($minutes);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
