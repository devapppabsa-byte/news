<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Mail\NovedadAlert;
use App\Models\NotificationDelivery;
use App\Services\Notifications\MailFromResolver;
use App\Services\Notifications\NovedadNotifier;
use App\Support\SecretSanitizer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNovedadAlertJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    /** @var array<int, int> */
    public array $backoff;

    public int $timeout;

    public function __construct(
        public readonly int $deliveryId,
    ) {
        $this->tries = (int) config('notifications.queue.tries', 3);
        $this->backoff = (array) config('notifications.queue.backoff', [60, 300, 900]);
        $this->timeout = (int) config('notifications.queue.timeout', 120);
        $this->onQueue((string) config('notifications.queue.name', 'notifications'));
        $this->onConnection(config('notifications.queue.connection') ?: null);
    }

    public function uniqueId(): string
    {
        return 'novedad-alert-'.$this->deliveryId;
    }

    public function uniqueFor(): int
    {
        return (int) config('notifications.queue.unique_for', 3600);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->expireAfter((int) config('notifications.queue.timeout', 120) + 30),
        ];
    }

    public function handle(NovedadNotifier $notifier, MailFromResolver $mailFrom): void
    {
        $delivery = NotificationDelivery::query()
            ->with(['monitoredTopic.user', 'user'])
            ->find($this->deliveryId);

        if ($delivery === null || $delivery->status === DeliveryStatus::Sent) {
            return;
        }

        $item = $delivery->monitoredTopic?->newsItems()
            ->where('news_entry_id', $delivery->news_entry_id)
            ->first();

        if ($item === null || $delivery->monitoredTopic === null) {
            $delivery->markSkipped('Novedad no longer available.');

            return;
        }

        if (! $notifier->isEligible($delivery->monitoredTopic, $item)) {
            $delivery->markSkipped('Recipient is no longer eligible.');

            return;
        }

        $user = $delivery->user;

        if ($user === null) {
            $delivery->markSkipped('Recipient missing.');

            return;
        }

        $delivery->increment('attempts');

        $locale = $user->preference?->locale ?: config('app.locale');

        Mail::to($user->email)
            ->locale($locale)
            ->send(new NovedadAlert($delivery->monitoredTopic, $item, $mailFrom->resolve()));

        $delivery->markSent();
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Novedad alert delivery failed permanently', [
            'delivery_id' => $this->deliveryId,
            'message' => $exception === null ? null : SecretSanitizer::clean($exception->getMessage()),
        ]);

        NotificationDelivery::query()
            ->find($this->deliveryId)
            ?->markFailed($exception === null ? 'Delivery failed.' : SecretSanitizer::clean($exception->getMessage()));
    }
}
