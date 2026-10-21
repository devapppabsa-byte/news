<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Services\Notifications\WebPushSender;
use App\Support\SecretSanitizer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class SendWebPushNotificationJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    /** @var array<int, int> */
    public array $backoff;

    public int $timeout;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly int $subscriptionId,
        public readonly array $payload,
    ) {
        $this->tries = (int) config('webpush.queue.tries', 3);
        $this->backoff = (array) config('webpush.queue.backoff', [60, 300, 900]);
        $this->timeout = (int) config('webpush.queue.timeout', 120);
        $this->onQueue((string) config('webpush.queue.name', 'notifications'));
        $this->onConnection(config('webpush.queue.connection') ?: null);
    }

    public function uniqueId(): string
    {
        $tag = (string) ($this->payload['tag'] ?? '');

        return 'webpush-'.$this->subscriptionId.'-'.$tag;
    }

    public function uniqueFor(): int
    {
        return (int) config('webpush.queue.unique_for', 3600);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->expireAfter((int) config('webpush.queue.timeout', 120) + 30),
        ];
    }

    public function handle(WebPushSender $sender): void
    {
        $subscription = PushSubscription::query()
            ->active()
            ->find($this->subscriptionId);

        if ($subscription === null) {
            return;
        }

        $sender->send([$subscription], $this->payload);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Web push delivery failed permanently', [
            'subscription_id' => $this->subscriptionId,
            'message' => $exception === null ? null : SecretSanitizer::clean($exception->getMessage()),
        ]);
    }
}
