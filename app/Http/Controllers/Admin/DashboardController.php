<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\RunStatus;
use App\Enums\TopicStatus;
use App\Http\Controllers\Controller;
use App\Models\MonitoredTopic;
use App\Models\NewsItem;
use App\Models\NotificationDelivery;
use App\Models\TopicRun;
use App\Models\User;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(SettingsRepository $settings): View
    {
        $windowDays = max(1, $settings->integer(SettingKeys::METRICS_WINDOW_DAYS)
            ?? (int) config('monitoring.metrics.window_days', 30));

        $since = now()->subDays($windowDays);

        $runs = TopicRun::query()->where('created_at', '>=', $since);

        $runsTotal = (clone $runs)->count();
        $runsSuccessful = (clone $runs)->where('status', RunStatus::Success)->count();
        $runsFailed = (clone $runs)->where('status', RunStatus::Failed)->count();
        $averageDuration = (clone $runs)->avg('duration_ms');
        $resultsTotal = (int) (clone $runs)->sum('results_count');

        $aiUsage = (clone $runs)
            ->whereNotNull('provider_usage')
            ->selectRaw("coalesce(json_unquote(json_extract(provider_usage, '$.ai_provider')), 'unknown') as provider")
            ->selectRaw('count(*) as runs')
            ->selectRaw("sum(coalesce(cast(json_unquote(json_extract(provider_usage, '$.ai_tokens')) as unsigned), 0)) as tokens")
            ->groupBy('provider')
            ->orderByDesc('tokens')
            ->get();

        $aiTokens = (int) $aiUsage->sum('tokens');

        $costByUser = TopicRun::query()
            ->where('topic_runs.created_at', '>=', $since)
            ->join('monitored_topics', 'monitored_topics.id', '=', 'topic_runs.monitored_topic_id')
            ->join('users', 'users.id', '=', 'monitored_topics.user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('cost')
            ->limit(10)
            ->get([
                'users.id',
                'users.name',
                'users.email',
                DB::raw('count(*) as runs'),
                DB::raw('sum(topic_runs.results_count) as results'),
                DB::raw('sum(topic_runs.estimated_cost) as cost'),
            ]);

        return view('admin.dashboard', [
            'windowDays' => $windowDays,
            'metrics' => [
                'users_total' => User::query()->count(),
                'users_new' => User::query()->where('created_at', '>=', $since)->count(),
                'topics_total' => MonitoredTopic::query()->count(),
                'topics_active' => MonitoredTopic::query()->where('status', TopicStatus::Active)->count(),
                'runs_total' => $runsTotal,
                'runs_successful' => $runsSuccessful,
                'runs_failed' => $runsFailed,
                'runs_success_rate' => $runsTotal > 0 ? round($runsSuccessful / $runsTotal * 100, 1) : null,
                'average_duration_seconds' => $averageDuration === null ? null : round($averageDuration / 1000, 1),
                'results_total' => $resultsTotal,
                'news_items' => NewsItem::query()->where('created_at', '>=', $since)->count(),
                'notifications_sent' => NotificationDelivery::query()
                    ->where('channel', 'mail')
                    ->where('status', DeliveryStatus::Sent)
                    ->where('created_at', '>=', $since)
                    ->count(),
                'notifications_failed' => NotificationDelivery::query()
                    ->where('channel', 'mail')
                    ->where('status', DeliveryStatus::Failed)
                    ->where('created_at', '>=', $since)
                    ->count(),
                'estimated_cost' => (float) (clone $runs)->sum('estimated_cost'),
                'ai_tokens' => $aiTokens,
            ],
            'aiUsage' => $aiUsage,
            'costByUser' => $costByUser,
        ]);
    }
}
