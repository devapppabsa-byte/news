<?php

use App\Jobs\RunMonitoredTopicSearchJob;
use App\Models\MonitoredTopic;
use App\Services\Notifications\NovedadDigest;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('topics:run-due', function () {
    $topics = MonitoredTopic::query()->due()->get();

    $topics->each(fn (MonitoredTopic $topic) => RunMonitoredTopicSearchJob::dispatch($topic->id));

    $this->info("Queued searches for {$topics->count()} monitored topic(s).");
})->purpose('Queue searches for every monitored topic that is due');

Schedule::command('topics:run-due')->everyFifteenMinutes()->withoutOverlapping();

Artisan::command('novedades:send-digests', function () {
    $since = now()->subDay();

    $queued = app(NovedadDigest::class)->queueFor($since);

    $this->info("Queued digests for {$queued} user(s).");
})->purpose('Queue daily novedades summaries for every subscribed user');

/*
 * The digest hour is a runtime setting, so the schedule is evaluated hourly
 * and the command only runs during the configured hour. Reading the setting at
 * boot time would freeze its value for the lifetime of the scheduler.
 */
Schedule::call(function (): void {
    $hour = app(SettingsRepository::class)->integer(SettingKeys::DIGEST_AT);

    if ((int) now()->format('H') !== $hour) {
        return;
    }

    Artisan::call('novedades:send-digests');
})->hourly()->name('novedades:send-digests')->withoutOverlapping();

Schedule::command('data:purge-expired')->daily()->withoutOverlapping();
