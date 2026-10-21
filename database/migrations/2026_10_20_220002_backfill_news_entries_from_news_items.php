<?php

use App\Support\ContentHasher;
use App\Support\UrlNormalizer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill news_entries from the results already stored in news_items and
     * link each existing row to its global entry.
     */
    public function up(): void
    {
        $normalizer = new UrlNormalizer;
        $hasher = new ContentHasher;

        DB::table('news_items')
            ->whereNull('news_entry_id')
            ->orderBy('id')
            ->chunkById(200, function ($items) use ($normalizer, $hasher): void {
                foreach ($items as $item) {
                    $url = (string) ($item->url ?? '');

                    if ($url === '') {
                        continue;
                    }

                    $urlHash = $normalizer->hash($url);
                    $entryId = DB::table('news_entries')->where('url_hash', $urlHash)->value('id');

                    if ($entryId === null) {
                        $entryId = DB::table('news_entries')->insertGetId([
                            'title' => $item->title,
                            'url' => $url,
                            'normalized_url' => $normalizer->normalize($url),
                            'url_hash' => $urlHash,
                            'content_hash' => $hasher->hash($item->title, $item->body),
                            'source_name' => $item->source_domain,
                            'source_domain' => $item->source_domain,
                            'published_at' => $item->published_at,
                            'discovered_at' => $item->collected_at ?? $item->created_at,
                            'summary' => $item->ai_summary,
                            'relevance_score' => $item->ai_relevance,
                            'status' => 'existing',
                            'created_at' => $item->created_at,
                            'updated_at' => $item->updated_at,
                        ]);
                    }

                    DB::table('news_items')->where('id', $item->id)->update([
                        'news_entry_id' => $entryId,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('news_items')->update(['news_entry_id' => null]);
        DB::table('news_entries')->truncate();
    }
};
