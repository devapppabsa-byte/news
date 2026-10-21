<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Push notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (! $configured)
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
                    {{ __('Web push is not configured yet. Generate VAPID keys with "php artisan webpush:vapid" and add them to your environment.') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-6"
                 x-data="pushPanel({
                     vapidPublicKey: @js($vapidPublicKey),
                     csrfToken: @js(csrf_token()),
                     subscriptions: @js($subscriptions->map(fn ($s) => ['id' => $s->id, 'label' => $s->device_label, 'expired' => $s->isExpired(), 'last_seen_at' => optional($s->last_seen_at)->toDateTimeString()])),
                 })">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ __('This device') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('Enable browser notifications to receive novedades even when this page is closed.') }}
                    </p>
                </div>

                <p class="text-sm" x-show="! supported">
                    <span class="text-red-600">{{ __('This browser does not support web push notifications.') }}</span>
                </p>

                <div class="flex flex-wrap items-center gap-3" x-show="supported">
                    <x-primary-button type="button" @click="enable()" x-show="! subscribed" ::disabled="busy">
                        {{ __('Enable notifications') }}
                    </x-primary-button>

                    <button type="button" @click="disable()" x-show="subscribed"
                        class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300">
                        {{ __('Disable notifications') }}
                    </button>

                    <span class="text-sm text-gray-500" x-show="busy">{{ __('Working…') }}</span>
                    <span class="text-sm text-green-600" x-show="message" x-text="message"></span>
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Send a test') }}</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ __('We will send a push notification to every device you have registered.') }}
                    </p>

                    <form method="post" action="{{ route('push.test.send') }}" class="mt-3">
                        @csrf
                        <x-primary-button>{{ __('Send test notification') }}</x-primary-button>
                    </form>

                    @if (session('status') === 'push-test-queued')
                        <p class="mt-2 text-sm text-green-600">{{ __('Test notification queued.') }}</p>
                    @elseif (session('status') === 'push-no-subscriptions')
                        <p class="mt-2 text-sm text-amber-600">{{ __('No active device found. Enable notifications first.') }}</p>
                    @elseif (session('status') === 'push-subscription-removed')
                        <p class="mt-2 text-sm text-green-600">{{ __('Subscription removed.') }}</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Registered devices') }}</h3>

                    @if ($subscriptions->isEmpty())
                        <p class="mt-1 text-sm text-gray-600">{{ __('No devices registered yet.') }}</p>
                    @else
                        <ul class="mt-3 divide-y divide-gray-100">
                            @foreach ($subscriptions as $subscription)
                                <li class="flex items-center justify-between py-2">
                                    <div>
                                        <p class="text-sm text-gray-800">{{ $subscription->device_label ?: __('Unknown device') }}</p>
                                        <p class="text-xs text-gray-500">
                                            {{ $subscription->last_seen_at?->diffForHumans() }}
                                            @if ($subscription->isExpired())
                                                · <span class="text-red-500">{{ __('expired') }}</span>
                                            @endif
                                        </p>
                                    </div>
                                    <form method="post" action="{{ route('push.subscriptions.destroy', $subscription) }}">
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="text-xs text-red-600 hover:underline">{{ __('Remove') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function pushPanel(config) {
            return {
                vapidPublicKey: config.vapidPublicKey,
                csrfToken: config.csrfToken,
                subscriptions: config.subscriptions,
                subscribed: false,
                busy: false,
                message: '',
                supported: false,

                async init() {
                    this.supported = window.NewsPush?.supported() ?? false;

                    if (this.supported) {
                        this.subscribed = await window.NewsPush.isSubscribed();

                        if (!this.subscribed) {
                            await window.NewsPush.registerServiceWorker();
                        }
                    }
                },

                async enable() {
                    this.busy = true;
                    this.message = '';

                    try {
                        const result = await window.NewsPush.subscribe(this.vapidPublicKey, {
                            csrfToken: this.csrfToken,
                            deviceLabel: navigator.platform || null,
                        });

                        if (result.ok) {
                            this.subscribed = true;
                            this.message = @js(__('Notifications enabled.'));
                        } else {
                            this.message = @js(__('Could not enable notifications.')) + ' (' + result.reason + ')';
                        }
                    } finally {
                        this.busy = false;
                    }
                },

                async disable() {
                    this.busy = true;
                    this.message = '';

                    try {
                        const active = this.subscriptions.find((s) => ! s.expired);
                        await window.NewsPush.unsubscribe({
                            csrfToken: this.csrfToken,
                            subscriptionId: active?.id,
                        });

                        this.subscribed = false;
                        this.message = @js(__('Notifications disabled.'));
                    } finally {
                        this.busy = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
