<?php

namespace App\Services\Novedades;

use App\Models\NewsEntry;
use App\Support\ContentHasher;
use App\Support\UrlNormalizer;

class NewsEntryRepository
{
    public function __construct(
        protected UrlNormalizer $normalizer,
        protected ContentHasher $hasher,
    ) {}

    /**
     * Find a globally stored entry by its canonical URL hash.
     */
    public function findByUrlHash(string $urlHash): ?NewsEntry
    {
        return NewsEntry::query()->where('url_hash', $urlHash)->first();
    }

    /**
     * Find a globally stored entry whose content matches, even when the URL
     * changed. Only used when content hashing is enabled.
     */
    public function findByContentHash(string $contentHash): ?NewsEntry
    {
        if (! (bool) config('novedades.content_hash', true)) {
            return null;
        }

        return NewsEntry::query()->where('content_hash', $contentHash)->first();
    }

    /**
     * Locate an existing global entry for a URL/content pair, preferring the
     * exact URL match. A changed URL with identical content is not "new".
     */
    public function findExisting(string $url, ?string $title, ?string $snippet): ?NewsEntry
    {
        $existing = $this->findByUrlHash($this->normalizer->hash($url));

        if ($existing !== null) {
            return $existing;
        }

        return $this->findByContentHash($this->hasher->hash($title, $snippet));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $url, array $attributes = []): NewsEntry
    {
        return NewsEntry::query()->create(array_merge([
            'url' => $url,
            'normalized_url' => $this->normalizer->normalize($url),
            'url_hash' => $this->normalizer->hash($url),
        ], $attributes));
    }

    public function contentHash(?string $title, ?string $snippet): string
    {
        return $this->hasher->hash($title, $snippet);
    }
}
