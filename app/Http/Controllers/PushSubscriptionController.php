<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PushSubscriptionController extends Controller
{
    /**
     * Store or refresh the push subscription for the current browser. The
     * endpoint is unique, so re-registering the same browser updates the
     * existing row instead of duplicating it.
     */
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $endpoint = $validated['endpoint'];

        $subscription = PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($endpoint)],
            [
                'user_id' => $request->user()->id,
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
                'device_label' => $validated['device_label'] ?? null,
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
                'expired_at' => null,
            ],
        );

        return response()->json([
            'id' => $subscription->id,
            'active' => ! $subscription->isExpired(),
        ], 201);
    }

    /**
     * Remove a push subscription owned by the current user.
     */
    public function destroy(Request $request, PushSubscription $pushSubscription): RedirectResponse|JsonResponse
    {
        abort_unless($pushSubscription->user_id === $request->user()->id, 403);

        $pushSubscription->delete();

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }

        return Redirect::route('push.test')->with('status', 'push-subscription-removed');
    }
}
