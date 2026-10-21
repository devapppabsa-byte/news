<?php

namespace App\Enums;

enum SourceType: string
{
    case Preferred = 'preferred';
    case Excluded = 'excluded';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case): string => $case->value, self::cases());
    }
}
