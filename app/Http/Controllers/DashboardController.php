<?php

namespace App\Http\Controllers;

use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\TopicRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $latestNews = NewsItem::query()
            ->where('user_id', $user->id)
            ->with(['topic', 'run'])
            ->latest('created_at')
            ->limit(10)
            ->get();

        $topics = MonitoredTopic::query()
            ->where('user_id', $user->id)
            ->withCount(['newsItems', 'runs'])
            ->get();

        $upcoming = $topics
            ->filter(fn (MonitoredTopic $topic) => $topic->next_run_at !== null)
            ->sortBy('next_run_at')
            ->take(5)
            ->values();

        $unreadCount = NewsItem::query()->where('user_id', $user->id)->where('is_read', false)->count();
        $savedCount = NewsItem::query()->where('user_id', $user->id)->where('is_saved', true)->count();
        $activeTopics = $topics->where('status', 'active')->count();

        $recentRuns = TopicRun::query()
            ->whereHas('monitoredTopic', fn ($q) => $q->where('user_id', $user->id))
            ->with(['monitoredTopic'])
            ->latest('id')
            ->limit(5)
            ->get();

        return view('dashboard', [
            'latestNews' => $latestNews,
            'upcoming' => $upcoming,
            'unreadCount' => $unreadCount,
            'savedCount' => $savedCount,
            'activeTopics' => $activeTopics,
            'recentRuns' => $recentRuns,
            'topicsCount' => $topics->count(),
        ]);
    }
}
