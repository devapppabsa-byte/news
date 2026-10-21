<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MonitoredTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class TopicController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $userId = $request->integer('user_id');

        $topics = MonitoredTopic::query()
            ->with(['user'])
            ->withCount('runs')
            ->with('runs', fn ($q) => $q->latest('id')->limit(1))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->when(in_array($status, ['active', 'paused'], true), fn ($q) => $q->where('status', $status))
            ->when($userId > 0, fn ($q) => $q->where('user_id', $userId))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.topics.index', [
            'topics' => $topics,
            'search' => $search,
            'status' => $status,
            'userId' => $userId,
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:pause,resume,run'],
            'topics' => ['required', 'array', 'min:1'],
            'topics.*' => ['integer', 'exists:monitored_topics,id'],
        ]);

        $topics = MonitoredTopic::query()->whereIn('id', $validated['topics'])->get();

        foreach ($topics as $topic) {
            if ($validated['action'] === 'pause') {
                $topic->pause();

                continue;
            }

            if ($validated['action'] === 'resume') {
                $topic->resume();

                continue;
            }

            if ($validated['action'] === 'run') {
                $topic->forceFill(['next_run_at' => now()])->save();
                Artisan::call('topics:run-due');
            }
        }

        return Redirect::route('admin.topics.index')->with('status', 'topics-bulk-updated');
    }
}
