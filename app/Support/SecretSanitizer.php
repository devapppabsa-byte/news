<?php

namespace App\Support;

use Illuminate\Support\Str;

class SecretSanitizer
{
    /**
     * Patterns that may leak provider credentials into logs or delivered mail.
     *
     * @var array<int, string>
     */
    protected const PATTERNS = [
        '/sk-ant-[A-Za-z0-9\-_]+/',
        '/sk-or-[A-Za-z0-9\-_]+/',
        '/sk-[A-Za-z0-9\-_]+/',
        '/Bearer\s+[A-Za-z0-9\-_.]+/i',
    ];

    /**
     * Replace anything that looks like a secret with a fixed token and cap the
     * total length so error messages stay readable and safe.
     */
    public static function clean(string $message, int $limit = 500): string
    {
        $clean = preg_replace(self::PATTERNS, '[redacted]', $message) ?? $message;

        return Str::limit($clean, $limit);
    }
}
