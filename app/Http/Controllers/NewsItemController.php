<?php

namespace App\Http\Controllers;

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsItemController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $topicId = $request->integer('topic');
        $filter = $request->string('filter')->toString();
        $search = trim($request->string('q')->toString());

        $topics = MonitoredTopic::query()->where('user_id', $user->id)->orderBy('name')->get(['id', 'name']);

        $news = NewsItem::query()
            ->where('user_id', $user->id)
            ->with(['topic', 'run'])
            ->when($topicId > 0, fn ($query) => $query->where('topic_id', $topicId))
            ->when($filter === 'unread', fn ($query) => $query->where('is_read', false))
            ->when($filter === 'saved', fn ($query) => $query->where('is_saved', true))
            ->when($filter === 'irrelevant', fn ($query) => $query->where('is_irrelevant', true))
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('source_domain', 'like', "%{$search}%");
            }))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('news-items.index', [
            'news' => $news,
            'topics' => $topics,
            'filters' => [
                'topic' => $topicId,
                'filter' => $filter,
                'q' => $search,
            ],
        ]);
    }

    public function show(NewsItem $newsItem): View
    {
        $this->authorize('view', $newsItem);

        $newsItem->load(['topic', 'run', 'user']);

        return view('news-items.show', [
            'newsItem' => $newsItem,
        ]);
    }

    /**
     * Toggle the read state of a news item for the current viewer.
     */
    public function read(Request $request, NewsItem $newsItem): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $newsItem);

        $newsItem->markRead(! $newsItem->is_read);

        if ($request->expectsJson()) {
            return response()->json(['is_read' => $newsItem->fresh()->is_read]);
        }

        return back();
    }

    /**
     * Toggle the saved state of a news item.
     */
    public function save(Request $request, NewsItem $newsItem): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $newsItem);

        $newsItem->markSaved(! $newsItem->is_saved);

        if ($request->expectsJson()) {
            return response()->json(['is_saved' => $newsItem->fresh()->is_saved]);
        }

        return back();
    }

    /**
     * Toggle the irrelevant state of a news item.
     */
    public function irrelevant(Request $request, NewsItem $newsItem): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $newsItem);

        $newsItem->markIrrelevant(! $newsItem->is_irrelevant);

        if ($request->expectsJson()) {
            return response()->json(['is_irrelevant' => $newsItem->fresh()->is_irrelevant]);
        }

        return back();
    }
}
