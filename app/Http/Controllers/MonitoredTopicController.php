<?php

namespace App\Http\Controllers;

use App\Enums\KeywordType;
use App\Enums\RunTrigger;
use App\Enums\SourceType;
use App\Enums\TopicStatus;
use App\Http\Requests\MonitoredTopic\StoreMonitoredTopicRequest;
use App\Http\Requests\MonitoredTopic\UpdateMonitoredTopicRequest;
use App\Jobs\RunMonitoredTopicSearchJob;
use App\Models\MonitoredTopic;
use App\Services\Settings\SettingsRepository;
use App\Support\RelevancePreferences;
use App\Support\SettingKeys;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MonitoredTopicController extends Controller
{
    /**
     * Display a listing of the user's monitored topics.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MonitoredTopic::class);

        $topics = $request->user()
            ->monitoredTopics()
            ->withCount(['keywords', 'sources', 'runs'])
            ->latest()
            ->paginate(15);

        return view('topics.index', [
            'topics' => $topics,
        ]);
    }

    /**
     * Show the form for creating a new monitored topic.
     */
    public function create(): View
    {
        $this->authorize('create', MonitoredTopic::class);

        return view('topics.create');
    }

    /**
     * Store a newly created monitored topic.
     */
    public function store(StoreMonitoredTopicRequest $request): RedirectResponse
    {
        $limit = app(SettingsRepository::class)->integer(SettingKeys::MAX_TOPICS_PER_USER);

        if ($limit > 0 && $request->user()->monitoredTopics()->count() >= $limit) {
            return back()
                ->withInput()
                ->withErrors(['name' => __('You have reached the maximum of :limit topics.', ['limit' => $limit])]);
        }

        $topic = $request->user()->monitoredTopics()->create(
            $this->topicAttributes($request, $request->user()->monitoredTopics())
        );

        $topic->scheduleNextRun();
        $topic->save();

        $this->syncKeywords($topic, $request->includeKeywords(), $request->excludeKeywords());
        $this->syncSources($topic, $request->preferredSources(), $request->excludedSources());

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'topic-created');
    }

    /**
     * Display the specified monitored topic.
     */
    public function show(Request $request, MonitoredTopic $topic): View
    {
        $this->authorize('view', $topic);

        $topic->load(['keywords', 'sources']);

        $runs = $topic->runs()
            ->latest()
            ->paginate(config('monitoring.runs_per_page'), ['*'], 'runs')
            ->withQueryString();

        $filter = $request->string('filter')->toString();

        $results = $topic->newsItems()
            ->with('newsEntry')
            ->when($filter === 'new', fn (Builder $query) => $query->where('novedad_status', 'new'))
            ->when($filter === 'saved', fn (Builder $query) => $query->where('is_saved', true))
            ->when($filter === 'unread', fn (Builder $query) => $query->where('is_read', false))
            ->when($filter === 'irrelevant', fn (Builder $query) => $query->where('is_irrelevant', true))
            ->latest('published_at')
            ->latest('id')
            ->paginate((int) config('novedades.per_page', 15), ['*'], 'results')
            ->withQueryString();

        return view('topics.show', [
            'topic' => $topic,
            'runs' => $runs,
            'results' => $results,
            'filter' => $filter,
        ]);
    }

    /**
     * Show the form for editing the specified monitored topic.
     */
    public function edit(MonitoredTopic $topic): View
    {
        $this->authorize('update', $topic);

        $topic->load(['keywords', 'sources']);

        return view('topics.edit', [
            'topic' => $topic,
        ]);
    }

    /**
     * Update the specified monitored topic.
     */
    public function update(UpdateMonitoredTopicRequest $request, MonitoredTopic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $topic->update(
            $this->topicAttributes($request, $request->user()->monitoredTopics(), $topic)
        );

        $topic->scheduleNextRun();
        $topic->save();

        $this->syncKeywords($topic, $request->includeKeywords(), $request->excludeKeywords());
        $this->syncSources($topic, $request->preferredSources(), $request->excludedSources());

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'topic-updated');
    }

    /**
     * Pause the specified monitored topic.
     */
    public function pause(MonitoredTopic $topic): RedirectResponse
    {
        $this->authorize('pause', $topic);

        $topic->update([
            'status' => TopicStatus::Paused,
            'next_run_at' => null,
        ]);

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'topic-paused');
    }

    /**
     * Resume the specified monitored topic.
     */
    public function resume(MonitoredTopic $topic): RedirectResponse
    {
        $this->authorize('resume', $topic);

        $topic->update(['status' => TopicStatus::Active]);
        $topic->scheduleNextRun();
        $topic->save();

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'topic-resumed');
    }

    /**
     * Remove the specified monitored topic.
     */
    public function destroy(MonitoredTopic $topic): RedirectResponse
    {
        $this->authorize('delete', $topic);

        $topic->delete();

        return redirect()
            ->route('topics.index')
            ->with('status', 'topic-deleted');
    }

    /**
     * Queue a manual search for the specified monitored topic.
     */
    public function run(MonitoredTopic $topic): RedirectResponse
    {
        $this->authorize('run', $topic);

        RunMonitoredTopicSearchJob::dispatch($topic->id, RunTrigger::Manual);

        return redirect()
            ->route('topics.show', $topic)
            ->with('status', 'topic-queued');
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     * @return array<string, mixed>
     */
    protected function topicAttributes(Request $request, $query, ?MonitoredTopic $topic = null): array
    {
        $validated = $request->validated();

        $relevance = $request->input('relevance', []);
        $preferences = new RelevancePreferences(
            minRelevance: (int) ($relevance['min_relevance'] ?? config('monitoring.relevance.min_relevance')),
            sort: (string) ($relevance['sort'] ?? config('monitoring.relevance.sort')),
            notifyThreshold: (int) ($relevance['notify_threshold'] ?? config('monitoring.relevance.notify_threshold')),
        );

        $slug = $this->uniqueSlug(
            $validated['name'],
            $query,
            $topic?->id,
        );

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'language' => $validated['language'],
            'country' => $validated['country'] ?? null,
            'frequency' => $validated['frequency'],
            'custom_interval_minutes' => $validated['custom_interval_minutes'] ?? null,
            'notify_email' => $request->boolean('notify_email'),
            'notify_push' => $request->boolean('notify_push'),
            'notification_mode' => $validated['notification_mode'] ?? null,
            'relevance_preferences' => $preferences,
        ];
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     */
    protected function uniqueSlug(string $name, $query, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'topic';
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($query, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  Builder<MonitoredTopic>  $query
     */
    protected function slugExists($query, string $slug, ?int $ignoreId): bool
    {
        return (clone $query)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();
    }

    /**
     * @param  array<int, string>  $include
     * @param  array<int, string>  $exclude
     */
    protected function syncKeywords(MonitoredTopic $topic, array $include, array $exclude): void
    {
        $topic->keywords()->delete();

        foreach ($include as $value) {
            $topic->keywords()->create([
                'type' => KeywordType::Include,
                'value' => $value,
            ]);
        }

        foreach ($exclude as $value) {
            $topic->keywords()->create([
                'type' => KeywordType::Exclude,
                'value' => $value,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $preferred
     * @param  array<int, string>  $excluded
     */
    protected function syncSources(MonitoredTopic $topic, array $preferred, array $excluded): void
    {
        $topic->sources()->delete();

        foreach ($preferred as $domain) {
            $topic->sources()->create([
                'type' => SourceType::Preferred,
                'domain' => strtolower($domain),
            ]);
        }

        foreach ($excluded as $domain) {
            $topic->sources()->create([
                'type' => SourceType::Excluded,
                'domain' => strtolower($domain),
            ]);
        }
    }
}
