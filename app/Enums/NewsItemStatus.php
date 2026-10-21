<?php

namespace App\Enums;

enum NewsItemStatus: string
{
    case New = 'new';
    case Existing = 'existing';
    case Irrelevant = 'irrelevant';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }
}
