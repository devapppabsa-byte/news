<?php

namespace App\Services\Notifications;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushSender
{
    /**
     * Send a payload to every active subscription of a user. Expired
     * subscriptions (404/410 responses) are marked so they are skipped later.
     * Returns the number of subscriptions that accepted the message.
     *
     * @param  iterable<PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $payload
     */
    public function send(iterable $subscriptions, array $payload): int
    {
        $webPush = $this->client();

        if ($webPush === null) {
            return 0;
        }

        $sent = 0;

        foreach ($subscriptions as $subscription) {
            $report = $webPush->sendOneNotification(
                $this->toTransportSubscription($subscription),
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ['TTL' => (int) config('webpush.ttl', 3600), 'urgency' => (string) config('webpush.urgency', 'normal')],
            );

            if ($report->isSuccess()) {
                $subscription->markSeen();
                $sent++;

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $subscription->markExpired();
            }
        }

        return $sent;
    }

    /**
     * Whether a VAPID key pair is configured. Without it nothing can be sent.
     */
    public function isConfigured(): bool
    {
        return filled(config('webpush.vapid.public_key'))
            && filled(config('webpush.vapid.private_key'));
    }

    /**
     * The public VAPID key shared with the browser to create a subscription.
     */
    public function publicKey(): ?string
    {
        return config('webpush.vapid.public_key');
    }

    protected function client(): ?WebPush
    {
        if (! (bool) config('webpush.enabled', true) || ! $this->isConfigured()) {
            return null;
        }

        return new WebPush([
            'VAPID' => [
                'subject' => (string) config('webpush.vapid.subject'),
                'publicKey' => (string) config('webpush.vapid.public_key'),
                'privateKey' => (string) config('webpush.vapid.private_key'),
            ],
        ]);
    }

    protected function toTransportSubscription(PushSubscription $subscription): Subscription
    {
        return new Subscription(
            $subscription->endpoint,
            $subscription->public_key,
            $subscription->auth_token,
            $subscription->content_encoding,
        );
    }
}
