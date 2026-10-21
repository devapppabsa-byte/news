<?php

namespace App\Support;

use Illuminate\Support\Str;

class ContentHasher
{
    /**
     * A stable fingerprint of the meaningful content of a result. Two results
     * that share the same title and snippet but have different URLs (e.g. a
     * changed tracking URL) produce the same hash.
     */
    public function hash(?string $title, ?string $snippet = null): string
    {
        $normalized = $this->normalize($title).'|'.$this->normalize($snippet);

        return hash('sha256', $normalized);
    }

    protected function normalize(?string $value): string
    {
        $value = Str::of((string) $value)
            ->lower()
            ->replaceMatches('/[^\p{L}\p{N}\s]+/u', ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        return $value;
    }
}
