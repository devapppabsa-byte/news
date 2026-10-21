<?php

namespace App\Jobs;

use App\Enums\DeliveryStatus;
use App\Mail\NovedadDigest as NovedadDigestMailable;
use App\Models\NotificationDelivery;
use App\Services\Notifications\MailFromResolver;
use App\Services\Notifications\NovedadDigest;
use App\Support\NotificationKey;
use App\Support\SecretSanitizer;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNovedadDigestJob implements ShouldBeUnique, ShouldQueue
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
        return 'novedad-digest-'.$this->deliveryId;
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

    public function handle(NovedadDigest $digest, MailFromResolver $mailFrom): void
    {
        $delivery = NotificationDelivery::query()->with('user.preference')->find($this->deliveryId);

        if ($delivery === null || $delivery->status === DeliveryStatus::Sent) {
            return;
        }

        $user = $delivery->user;

        if ($user === null || $user->isSuspended()) {
            $delivery->markSkipped('Recipient is no longer eligible.');

            return;
        }

        $preference = $user->preference;

        if ($preference !== null && ! $preference->wantsEmailNotifications()) {
            $delivery->markSkipped('Email notifications disabled.');

            return;
        }

        $since = $delivery->period_start !== null
            ? Carbon::instance($delivery->period_start)
            : now()->subDay();

        $items = $digest->itemsForUser($user->id, $since);

        if ($items->isEmpty()) {
            $delivery->markSkipped('No novedades to summarise.');

            return;
        }

        $delivery->increment('attempts');
        $delivery->forceFill(['items_count' => $items->count()])->save();

        $period = $delivery->period_start !== null
            ? NotificationKey::periodFor($delivery->period_start)
            : NotificationKey::periodFor();

        Mail::to($user->email)
            ->locale($preference?->locale ?: config('app.locale'))
            ->send(new NovedadDigestMailable($user->name, $items, $period, $mailFrom->resolve()));

        $delivery->markSent();
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Novedad digest delivery failed permanently', [
            'delivery_id' => $this->deliveryId,
            'message' => $exception === null ? null : SecretSanitizer::clean($exception->getMessage()),
        ]);

        NotificationDelivery::query()
            ->find($this->deliveryId)
            ?->markFailed($exception === null ? 'Delivery failed.' : SecretSanitizer::clean($exception->getMessage()));
    }
}
