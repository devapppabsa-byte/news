<?php

namespace App\Jobs\Middleware;

use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Closure;
use Illuminate\Support\Facades\Cache;

class LimitConcurrentSearches
{
    public function __construct(
        protected int $userId,
    ) {}

    /**
     * Acquire a global and a per-user slot before the job runs. Slots are
     * numbered atomic locks; when no slot is free the job is released back
     * onto the queue instead of failing.
     *
     * @param  Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        $settings = app(SettingsRepository::class);

        $ttl = (int) config('monitoring.queue.timeout', 900) + 60;
        $releaseAfter = (int) config('monitoring.concurrency.release_after', 30);

        $globalMax = max(1, $settings->integer(SettingKeys::CONCURRENCY_MAX_GLOBAL));
        $userMax = max(1, $settings->integer(SettingKeys::CONCURRENCY_MAX_PER_USER));

        $globalSlot = $this->acquire('search-concurrency:global', $globalMax, $ttl);
        $userSlot = $this->acquire('search-concurrency:user:'.$this->userId, $userMax, $ttl);

        if ($globalSlot === null || $userSlot === null) {
            if ($globalSlot !== null) {
                $globalSlot->release();
            }

            $job->release($releaseAfter);

            return;
        }

        try {
            $next($job);
        } finally {
            $globalSlot->release();
            $userSlot->release();
        }
    }

    /**
     * Try each numbered slot until one is acquired.
     */
    protected function acquire(string $prefix, int $max, int $ttl): ?object
    {
        for ($i = 0; $i < $max; $i++) {
            $lock = Cache::lock($prefix.':slot:'.$i, $ttl);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }
}
