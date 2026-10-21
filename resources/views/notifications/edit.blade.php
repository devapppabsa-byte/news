<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notification settings') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'preferences-updated')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Notification settings updated.') }}</div>
            @endif

            @if (! $notificationsEnabled)
                <div class="p-4 bg-yellow-50 text-yellow-800 rounded-md">{{ __('Email notifications are disabled globally by the administrator.') }}</div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <form method="POST" action="{{ route('notifications.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="locale" :value="__('Locale')" />
                            <x-text-input id="locale" name="locale" type="text" class="mt-1 block w-full" :value="old('locale', $preference->locale ?? 'es')" />
                            <x-input-error :messages="$errors->get('locale')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="timezone" :value="__('Timezone')" />
                            <x-text-input id="timezone" name="timezone" type="text" class="mt-1 block w-full" :value="old('timezone', $preference->timezone ?? config('app.timezone'))" />
                            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
                        </div>
                    </div>

                    <div class="space-y-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="email_notifications" value="0">
                            <input type="checkbox" name="email_notifications" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('email_notifications', $preference->email_notifications ?? true))>
                            {{ __('Email notifications') }}
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="push_notifications" value="0">
                            <input type="checkbox" name="push_notifications" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('push_notifications', $preference->push_notifications ?? false))>
                            {{ __('Push notifications') }}
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="push_private" value="0">
                            <input type="checkbox" name="push_private" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" @checked(old('push_private', $preference->push_private ?? true))>
                            {{ __('Private push content') }}
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="notification_mode" :value="__('Delivery mode')" />
                            <select id="notification_mode" name="notification_mode" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                @foreach (\App\Enums\NotificationMode::cases() as $mode)
                                    <option value="{{ $mode->value }}" @selected(old('notification_mode', $preference->notification_mode?->value ?? 'digest') === $mode->value)>{{ __($mode->label()) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('notification_mode')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="digest_frequency" :value="__('Digest frequency')" />
                            <select id="digest_frequency" name="digest_frequency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="daily" @selected(old('digest_frequency', $preference->digest_frequency ?? 'daily') === 'daily')>{{ __('Daily') }}</option>
                                <option value="weekly" @selected(old('digest_frequency', $preference->digest_frequency ?? 'daily') === 'weekly')>{{ __('Weekly') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('digest_frequency')" class="mt-2" />
                        </div>
                    </div>

                    <x-primary-button>{{ __('Save notification settings') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
