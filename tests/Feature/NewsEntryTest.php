<?php

use App\Models\MonitoredTopic;
use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

test('news entries share one global record across topics', function () {
    $user = User::factory()->create();
    $topicA = MonitoredTopic::factory()->for($user)->create();
    $topicB = MonitoredTopic::factory()->for($user)->create();

    $entry = NewsEntry::factory()->create();

    NewsItem::factory()->forTopic($topicA)->create(['news_entry_id' => $entry->id]);
    NewsItem::factory()->forTopic($topicB)->create(['news_entry_id' => $entry->id]);

    expect($entry->newsItems()->count())->toBe(2)
        ->and($entry->newsItems()->pluck('topic_id')->unique()->count())->toBe(2);
});

test('the url hash is unique', function () {
    NewsEntry::factory()->create(['url_hash' => 'abc']);

    expect(fn () => NewsEntry::factory()->create(['url_hash' => 'abc']))
        ->toThrow(UniqueConstraintViolationException::class);
});

test('deleting an entry detaches its items instead of deleting them', function () {
    $entry = NewsEntry::factory()->create();
    $item = NewsItem::factory()->create(['news_entry_id' => $entry->id]);

    $entry->delete();

    expect(NewsItem::query()->find($item->id))->not->toBeNull()
        ->and(NewsItem::query()->find($item->id)->news_entry_id)->toBeNull();
});
