<?php

namespace App\Jobs;

use App\Actions\RunMonitoredTopicSearch;
use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Jobs\Middleware\LimitConcurrentSearches;
use App\Models\MonitoredTopic;
use App\Models\TopicRun;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RunMonitoredTopicSearchJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    /**
     * Hard attempt limit. Reached attempts mark the run as failed.
     */
    public int $tries;

    /**
     * Progressive backoff between attempts, in seconds.
     *
     * @var array<int, int>
     */
    public array $backoff;

    public int $timeout;

    public function __construct(
        public readonly int $topicId,
        public readonly RunTrigger $trigger = RunTrigger::Scheduled,
    ) {
        $this->tries = (int) config('monitoring.queue.tries', 3);
        $this->backoff = (array) config('monitoring.queue.backoff', [60, 300, 900]);
        $this->timeout = (int) config('monitoring.queue.timeout', 900);
        $this->onQueue((string) config('monitoring.queue.name', 'searches'));
        $this->onConnection(config('monitoring.queue.connection') ?: null);
    }

    public function uniqueId(): string
    {
        return 'monitored-topic-'.$this->topicId;
    }

    public function uniqueFor(): int
    {
        return (int) config('monitoring.queue.unique_for', 3600);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        $topic = MonitoredTopic::query()->find($this->topicId);

        $overlap = (new WithoutOverlapping($this->uniqueId()))
            ->expireAfter((int) config('monitoring.queue.timeout', 900) + 60)
            ->releaseAfter((int) config('monitoring.concurrency.release_after', 30));

        if ($topic === null) {
            return [$overlap];
        }

        return [$overlap, new LimitConcurrentSearches($topic->user_id)];
    }

    public function handle(RunMonitoredTopicSearch $action): void
    {
        $topic = MonitoredTopic::query()->find($this->topicId);

        if ($topic === null) {
            return;
        }

        $run = $this->queuedRun($topic);

        if (! $topic->isActive()) {
            $run?->update([
                'status' => RunStatus::Failed,
                'error_message' => 'Topic is paused.',
                'finished_at' => now(),
            ]);

            return;
        }

        $action->execute($topic, $this->trigger, $run);
    }

    /**
     * Reuse the run created by a previous attempt of this job, or create one if
     * the job is running for the first time.
     */
    protected function queuedRun(MonitoredTopic $topic): TopicRun
    {
        return $topic->runs()
            ->whereIn('status', [RunStatus::Queued, RunStatus::Running])
            ->latest('id')
            ->first()
            ?? $topic->runs()->create([
                'trigger' => $this->trigger,
                'status' => RunStatus::Queued,
                'queued_at' => now(),
            ]);
    }

    /**
     * Called when the job exhausts every attempt. The run is marked failed and
     * a structured, sanitized entry is logged.
     */
    public function failed(?\Throwable $exception): void
    {
        $topic = MonitoredTopic::query()->find($this->topicId);

        Log::error('Monitored topic search job failed permanently', [
            'topic_id' => $this->topicId,
            'message' => $exception === null
                ? null
                : Str::limit(preg_replace('/sk-[A-Za-z0-9\-_]+/', '[redacted]', $exception->getMessage()) ?? '', 500),
        ]);

        if ($topic === null) {
            return;
        }

        $topic->runs()
            ->whereIn('status', [RunStatus::Queued, RunStatus::Running])
            ->latest('id')
            ->first()
            ?->update([
                'status' => RunStatus::Failed,
                'error_message' => $exception === null
                    ? 'Search job failed.'
                    : Str::limit(preg_replace('/sk-[A-Za-z0-9\-_]+/', '[redacted]', $exception->getMessage()) ?? '', 500),
                'finished_at' => now(),
            ]);
    }
}
