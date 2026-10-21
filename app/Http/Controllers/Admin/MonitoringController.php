<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DeliveryStatus;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Http\Controllers\Controller;
use App\Models\NotificationDelivery;
use App\Models\TopicRun;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MonitoringController extends Controller
{
    /**
     * Search runs, including failures and queued/running work.
     */
    public function runs(Request $request): View
    {
        $status = RunStatus::tryFrom($request->string('status')->toString());
        $trigger = RunTrigger::tryFrom($request->string('trigger')->toString());
        $search = trim($request->string('q')->toString());

        $runs = TopicRun::query()
            ->with(['monitoredTopic.user'])
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($trigger !== null, fn ($query) => $query->where('trigger', $trigger->value))
            ->when($search !== '', fn ($query) => $query->whereHas(
                'monitoredTopic',
                fn ($query) => $query->where('name', 'like', "%{$search}%"),
            ))
            ->latest('id')
            ->paginate((int) config('monitoring.runs_per_page', 15))
            ->withQueryString();

        return view('admin.monitoring.runs', [
            'runs' => $runs,
            'statuses' => RunStatus::cases(),
            'triggers' => RunTrigger::cases(),
            'filters' => [
                'status' => $status?->value,
                'trigger' => $trigger?->value,
                'q' => $search,
            ],
        ]);
    }

    /**
     * Email deliveries with their outcomes.
     */
    public function deliveries(Request $request): View
    {
        $status = DeliveryStatus::tryFrom($request->string('status')->toString());
        $search = trim($request->string('q')->toString());

        $deliveries = NotificationDelivery::query()
            ->with(['user', 'monitoredTopic'])
            ->when($status !== null, fn ($query) => $query->where('status', $status->value))
            ->when($search !== '', fn ($query) => $query->whereHas(
                'user',
                fn ($query) => $query->where('email', 'like', "%{$search}%"),
            ))
            ->latest('id')
            ->paginate((int) config('monitoring.runs_per_page', 15))
            ->withQueryString();

        return view('admin.monitoring.deliveries', [
            'deliveries' => $deliveries,
            'statuses' => DeliveryStatus::cases(),
            'filters' => [
                'status' => $status?->value,
                'q' => $search,
            ],
        ]);
    }

    public function errors(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $failedRuns = TopicRun::query()
            ->with(['monitoredTopic.user'])
            ->where('status', RunStatus::Failed)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('monitoredTopic', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhere('error_message', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(15, ['*'], 'runs_page')
            ->withQueryString();

        $failedDeliveries = NotificationDelivery::query()
            ->with(['user', 'monitoredTopic'])
            ->where('status', DeliveryStatus::Failed)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->whereHas('user', fn ($q) => $q->where('email', 'like', "%{$search}%"))
                    ->orWhere('error_message', 'like', "%{$search}%");
            }))
            ->latest('id')
            ->paginate(15, ['*'], 'del_page')
            ->withQueryString();

        return view('admin.monitoring.errors', [
            'failedRuns' => $failedRuns,
            'failedDeliveries' => $failedDeliveries,
            'search' => $search,
        ]);
    }
}
