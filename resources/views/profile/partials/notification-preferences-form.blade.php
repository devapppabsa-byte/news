@php
    $mode = old('notification_mode', $preference?->notification_mode?->value ?? config('notifications.default_mode', 'digest'));
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Notification preferences') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Choose how we email you about new novedades across all your topics.') }}
        </p>
    </header>

    <form method="post" action="{{ route('preferences.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex items-center gap-2">
            <input type="checkbox" id="email_notifications" name="email_notifications" value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('email_notifications', $preference?->email_notifications ?? true)) />
            <x-input-label for="email_notifications" :value="__('Receive email notifications')" />
        </div>

        <div>
            <x-input-label for="notification_mode" :value="__('Default notification mode')" />
            <select id="notification_mode" name="notification_mode"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach (\App\Enums\NotificationMode::cases() as $notificationMode)
                    <option value="{{ $notificationMode->value }}" @selected($mode === $notificationMode->value)>
                        {{ __($notificationMode->label()) }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('notification_mode')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="digest_frequency" :value="__('Summary frequency')" />
                <select id="digest_frequency" name="digest_frequency"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="daily" @selected(old('digest_frequency', $preference?->digest_frequency ?? 'daily') === 'daily')>{{ __('Daily') }}</option>
                    <option value="weekly" @selected(old('digest_frequency', $preference?->digest_frequency ?? 'daily') === 'weekly')>{{ __('Weekly') }}</option>
                </select>
                <x-input-error :messages="$errors->get('digest_frequency')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="locale" :value="__('Language (ISO-639-1)')" />
                <x-text-input id="locale" name="locale" type="text" maxlength="2" class="mt-1 block w-full"
                    :value="old('locale', $preference?->locale ?? 'es')" required />
                <x-input-error :messages="$errors->get('locale')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="timezone" :value="__('Timezone')" />
            <x-text-input id="timezone" name="timezone" type="text" class="mt-1 block w-full"
                :value="old('timezone', $preference?->timezone ?? config('app.timezone'))" required />
            <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
        </div>

        <div class="border-t border-gray-100 pt-6 space-y-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Browser notifications') }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Receive instant alerts in your browser. Enable them per device from the test page.') }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="push_notifications" name="push_notifications" value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked(old('push_notifications', $preference?->push_notifications ?? true)) />
                <x-input-label for="push_notifications" :value="__('Enable push notifications')" />
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="push_private" name="push_private" value="1"
                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    @checked(old('push_private', $preference?->push_private ?? true)) />
                <x-input-label for="push_private" :value="__('Hide headline and summary on locked screens')" />
            </div>

            <p class="text-sm">
                <a href="{{ route('push.test') }}" class="text-indigo-600 hover:underline">
                    {{ __('Manage devices and send a test notification') }}
                </a>
            </p>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'preferences-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
