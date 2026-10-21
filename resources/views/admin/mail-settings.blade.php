<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Email settings') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $v = fn (string $key) => old($key, $values[$key] ?? null);
        $err = fn (string $key) => $errors->get($key);
    @endphp

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'mail-settings-saved')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Email settings saved.') }}</div>
            @endif

            @if (session('status') === 'mail-test-sent')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Test email sent.') }}</div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('Sender') }}</h3>
                <p class="text-sm text-gray-600">
                    {{ __('Used as the "from" address for every outgoing email. The transport itself is configured with the MAIL_* environment variables.') }}
                </p>

                <form method="POST" action="{{ route('admin.mail.settings.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="mail-from_address" :value="__('From address')" />
                        <x-text-input id="mail-from_address" name="mail[from_address]" type="email"
                            class="mt-1 block w-full" :value="$v('mail.from_address')" />
                        <x-input-error :messages="$err('mail.from_address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="mail-from_name" :value="__('From name')" />
                        <x-text-input id="mail-from_name" name="mail[from_name]" type="text"
                            class="mt-1 block w-full" :value="$v('mail.from_name')" />
                        <x-input-error :messages="$err('mail.from_name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label :value="__('Outgoing mail transport')" />
                        <p class="mt-1 text-sm text-gray-900">{{ $transport }}</p>
                    </div>

                    <div class="border-t pt-4">
                        <h4 class="font-semibold text-gray-900">{{ __('Notifications') }}</h4>

                        <input type="hidden" name="notifications[enabled]" value="0" />
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="notifications[enabled]" value="1"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                @checked((bool) $v('notifications.enabled')) />
                            {{ __('Send email notifications') }}
                        </label>
                        <x-input-error :messages="$err('notifications.enabled')" class="mt-2" />

                        <div class="mt-4">
                            <x-input-label for="notifications-min_relevance" :value="__('Minimum relevance to notify (0-100)')" />
                            <x-text-input id="notifications-min_relevance" name="notifications[min_relevance]"
                                type="number" min="0" max="100" class="mt-1 block w-full"
                                :value="$v('notifications.min_relevance')" />
                            <x-input-error :messages="$err('notifications.min_relevance')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-primary-button>{{ __('Save email settings') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('Send a test email') }}</h3>
                <p class="text-sm text-gray-600">
                    {{ __('Send a message using the current sender configuration to verify delivery.') }}
                </p>

                <form method="POST" action="{{ route('admin.mail.test') }}" class="space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="test-email" :value="__('Recipient')" />
                        <x-text-input id="test-email" name="email" type="email"
                            class="mt-1 block w-full" :value="old('email', auth()->user()->email)" />
                        <x-input-error :messages="$err('email')" class="mt-2" />
                    </div>

                    <x-secondary-button type="submit">{{ __('Send test email') }}</x-secondary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
