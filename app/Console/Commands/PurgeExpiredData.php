<?php

namespace App\Console\Commands;

use App\Models\NewsEntry;
use App\Models\NewsItem;
use App\Models\NotificationDelivery;
use App\Models\TopicRun;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PurgeExpiredData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:purge-expired {--dry-run : Report what would be deleted without removing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove expired topic runs, notification deliveries and news entries according to retention settings';

    public function __construct(
        protected SettingsRepository $settings,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $runsDays = max(0, $this->settings->integer(SettingKeys::RETENTION_TOPIC_RUNS_DAYS));
        $deliveriesDays = max(0, $this->settings->integer(SettingKeys::RETENTION_DELIVERIES_DAYS));
        $newsDays = max(0, $this->settings->integer(SettingKeys::RETENTION_NEWS_DAYS));

        $runsDeleted = $this->purge(TopicRun::query(), $runsDays, $dryRun);
        $deliveriesDeleted = $this->purge(NotificationDelivery::query(), $deliveriesDays, $dryRun);
        $newsDeleted = $this->purge(NewsItem::query(), $newsDays, $dryRun);
        $newsEntriesDeleted = $this->purge(NewsEntry::query(), $newsDays, $dryRun);

        $this->info("Expired data purged (dry-run: {$dryRun}): topic_runs={$runsDeleted}, notification_deliveries={$deliveriesDeleted}, news_items={$newsDeleted}, news_entries={$newsEntriesDeleted}");

        return self::SUCCESS;
    }

    /**
     * @param  Builder<Model>  $query
     */
    protected function purge($query, int $days, bool $dryRun): int
    {
        if ($days === 0) {
            return 0;
        }

        $cutoff = now()->subDays($days);
        $query = $query->where('created_at', '<', $cutoff);

        if ($dryRun) {
            return (int) $query->count();
        }

        return (int) $query->delete();
    }
}
