<?php

namespace App\Enums;

enum AISearchMode: string
{
    case External = 'external';
    case Integrated = 'integrated';
    case None = 'none';

    /**
     * The search mode values accepted by the configuration layer.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $mode): string => $mode->value, self::cases());
    }
}
