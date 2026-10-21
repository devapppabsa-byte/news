<?php

namespace App\Http\Controllers;

use App\Jobs\SendWebPushNotificationJob;
use App\Models\User;
use App\Services\Notifications\WebPushSender;
use App\Support\WebPushPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PushTestController extends Controller
{
    public function __construct(
        protected WebPushSender $sender,
    ) {}

    /**
     * Show the delivery test page with the user's registered devices.
     */
    public function show(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        return view('push.test', [
            'subscriptions' => $user->pushSubscriptions()
                ->orderByDesc('last_seen_at')
                ->get(),
            'vapidPublicKey' => $this->sender->publicKey(),
            'configured' => $this->sender->isConfigured(),
        ]);
    }

    /**
     * Queue a test notification to every active subscription of the user.
     */
    public function send(Request $request): RedirectResponse
    {
        $subscriptions = $request->user()->pushSubscriptions()->active()->get();

        if ($subscriptions->isEmpty()) {
            return back()->with('status', 'push-no-subscriptions');
        }

        $payload = WebPushPayload::forTest();

        foreach ($subscriptions as $subscription) {
            SendWebPushNotificationJob::dispatch($subscription->id, $payload);
        }

        return back()->with('status', 'push-test-queued');
    }
}
