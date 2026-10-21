<?php

namespace App\Enums;

enum NotificationMode: string
{
    case Digest = 'digest';
    case Individual = 'individual';
    case Both = 'both';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Digest => 'Daily summary',
            self::Individual => 'Immediate alert',
            self::Both => 'Both',
            self::None => 'Disabled',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $mode): string => $mode->value, self::cases());
    }

    public function sendsIndividual(): bool
    {
        return in_array($this, [self::Individual, self::Both], true);
    }

    public function sendsDigest(): bool
    {
        return in_array($this, [self::Digest, self::Both], true);
    }

    public function isDisabled(): bool
    {
        return $this === self::None;
    }
}
