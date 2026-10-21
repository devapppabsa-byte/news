<?php

use App\Jobs\Middleware\LimitConcurrentSearches;
use Illuminate\Support\Facades\Cache;

test('the concurrency middleware runs the job when slots are free', function () {
    config()->set('monitoring.concurrency.max_global', 5);
    config()->set('monitoring.concurrency.max_per_user', 2);

    $job = new class
    {
        public function release(int $seconds): void {}
    };

    $handled = false;
    $middleware = new LimitConcurrentSearches(userId: 1);

    $middleware->handle($job, function () use (&$handled): void {
        $handled = true;
    });

    expect($handled)->toBeTrue();
});

test('the concurrency middleware releases the job when the user limit is reached', function () {
    config()->set('monitoring.concurrency.max_per_user', 1);
    config()->set('monitoring.concurrency.max_global', 5);
    config()->set('monitoring.concurrency.release_after', 15);

    Cache::lock('search-concurrency:user:7:slot:0', 60)->get();

    $job = new class
    {
        public bool $released = false;

        public int $delay = 0;

        public function release(int $seconds): void
        {
            $this->released = true;
            $this->delay = $seconds;
        }
    };

    (new LimitConcurrentSearches(userId: 7))->handle($job, function (): void {
        $this->fail('The job should not run while the user slot is held.');
    });

    expect($job->released)->toBeTrue()
        ->and($job->delay)->toBe(15);
});

test('the concurrency middleware releases the job when the global limit is reached', function () {
    config()->set('monitoring.concurrency.max_global', 1);
    config()->set('monitoring.concurrency.max_per_user', 5);

    Cache::lock('search-concurrency:global:slot:0', 60)->get();

    $job = new class
    {
        public bool $released = false;

        public function release(int $seconds): void
        {
            $this->released = true;
        }
    };

    (new LimitConcurrentSearches(userId: 2))->handle($job, function (): void {
        $this->fail('The global limit should have released the job.');
    });

    expect($job->released)->toBeTrue();
});

test('slots are released after the job finishes', function () {
    config()->set('monitoring.concurrency.max_global', 1);
    config()->set('monitoring.concurrency.max_per_user', 1);

    $job = new class
    {
        public function release(int $seconds): void {}
    };

    $middleware = new LimitConcurrentSearches(userId: 3);

    $middleware->handle($job, function (): void {});

    Cache::flush();

    $again = new class
    {
        public bool $released = false;

        public function release(int $seconds): void
        {
            $this->released = true;
        }
    };

    $middleware->handle($again, function (): void {});

    expect($again->released)->toBeFalse();
});
