<?php

use App\Enums\RunStatus;
use App\Enums\RunTrigger;
use App\Jobs\RunMonitoredTopicSearchJob;
use App\Models\MonitoredTopic;
use App\Models\TopicRun;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Queue;

test('the scheduler queues a job for every due topic', function () {
    Queue::fake();

    $user = User::factory()->create();
    $due = MonitoredTopic::factory()->for($user)->due()->create();
    $future = MonitoredTopic::factory()->for($user)->create(['next_run_at' => now()->addDay()]);
    $paused = MonitoredTopic::factory()->for($user)->due()->paused()->create();

    $this->artisan('topics:run-due')->assertSuccessful();

    Queue::assertPushed(RunMonitoredTopicSearchJob::class, 1);
    Queue::assertPushed(fn (RunMonitoredTopicSearchJob $job): bool => $job->topicId === $due->id);
});

test('the scheduler does not queue anything when no topic is due', function () {
    Queue::fake();

    $user = User::factory()->create();
    MonitoredTopic::factory()->for($user)->create(['next_run_at' => now()->addDay()]);

    $this->artisan('topics:run-due')->assertSuccessful();

    Queue::assertNothingPushed();
});

test('the scheduled command is registered every fifteen minutes', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())
        ->filter(fn ($event): bool => str_contains($event->command ?? '', 'topics:run-due'));

    expect($events)->not->toBeEmpty()
        ->and($events->first()->expression)->toBe('*/15 * * * *');
});

test('a manual run queues a job instead of executing inline', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->create();

    $this->actingAs($user)
        ->post("/topics/{$topic->id}/run")
        ->assertRedirect()
        ->assertSessionHas('status', 'topic-queued');

    Queue::assertPushed(
        RunMonitoredTopicSearchJob::class,
        fn (RunMonitoredTopicSearchJob $job): bool => $job->topicId === $topic->id
            && $job->trigger === RunTrigger::Manual,
    );

    expect(TopicRun::query()->where('monitored_topic_id', $topic->id)->count())->toBe(0);
});

test('a paused topic queued job is not executed', function () {
    Queue::fake();

    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->paused()->create();

    RunMonitoredTopicSearchJob::dispatchSync($topic->id);

    expect(TopicRun::query()->where('monitored_topic_id', $topic->id)->count())->toBe(0);
});

test('a paused topic marks a queued run as failed when the job runs', function () {
    $user = User::factory()->create();
    $topic = MonitoredTopic::factory()->for($user)->paused()->create();

    $run = $topic->runs()->create([
        'trigger' => RunTrigger::Scheduled,
        'status' => RunStatus::Queued,
        'queued_at' => now(),
    ]);

    RunMonitoredTopicSearchJob::dispatchSync($topic->id);

    expect($run->fresh()->status)->toBe(RunStatus::Failed);
});
