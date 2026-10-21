<?php

namespace App\Http\Controllers;

use App\Enums\RunStatus;
use App\Models\MonitoredTopic;
use App\Models\TopicRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->string('status')->toString();
        $topicId = $request->integer('topic');
        $q = trim($request->string('q')->toString());

        $topics = MonitoredTopic::query()
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        $runs = TopicRun::query()
            ->whereHas('monitoredTopic', fn ($query) => $query->where('user_id', $user->id))
            ->with(['monitoredTopic'])
            ->when($status !== '' && RunStatus::tryFrom($status) !== null, fn ($query) => $query->where('status', $status))
            ->when($topicId > 0, fn ($query) => $query->where('monitored_topic_id', $topicId))
            ->when($q !== '', fn ($query) => $query->whereHas(
                'monitoredTopic',
                fn ($query) => $query->where('name', 'like', "%{$q}%"),
            ))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('search-history.index', [
            'runs' => $runs,
            'topics' => $topics,
            'statuses' => RunStatus::cases(),
            'filters' => [
                'status' => $status,
                'topic' => $topicId,
                'q' => $q,
            ],
        ]);
    }
}
