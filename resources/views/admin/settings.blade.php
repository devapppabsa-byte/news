<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Global settings') }}
        </h2>
    </x-slot>

    @include('admin.partials.nav')

    @php
        $v = fn (string $key) => old($key, $values[$key] ?? null);
        $name = fn (string $key) => '['.str_replace('.', '][', $key).']';
        $id = fn (string $key) => str_replace('.', '-', $key);
        $err = fn (string $key) => $errors->get($key);
    @endphp

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'settings-saved')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Settings saved.') }}</div>
            @endif

            @if (session('status') === 'settings-reset')
                <div class="p-4 bg-green-50 text-green-700 rounded-md">{{ __('Settings restored to their default values.') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-900">{{ __('Search intervals') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="monitoring-custom_interval-min_minutes" :value="__('Minimum custom interval (minutes)')" />
                            <x-text-input id="monitoring-custom_interval-min_minutes" name="monitoring[custom_interval][min_minutes]"
                                type="number" class="mt-1 block w-full" :value="$v('monitoring.custom_interval.min_minutes')" />
                            <x-input-error :messages="$err('monitoring.custom_interval.min_minutes')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monitoring-custom_interval-max_minutes" :value="__('Maximum custom interval (minutes)')" />
                            <x-text-input id="monitoring-custom_interval-max_minutes" name="monitoring[custom_interval][max_minutes]"
                                type="number" class="mt-1 block w-full" :value="$v('monitoring.custom_interval.max_minutes')" />
                            <x-input-error :messages="$err('monitoring.custom_interval.max_minutes')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-900">{{ __('Limits') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="topics-max_per_user" :value="__('Maximum topics per user (0 = unlimited)')" />
                            <x-text-input id="topics-max_per_user" name="topics[max_per_user]"
                                type="number" class="mt-1 block w-full" :value="$v('topics.max_per_user')" />
                            <x-input-error :messages="$err('topics.max_per_user')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notifications-limits-max_per_day" :value="__('Maximum notifications per day (0 = unlimited)')" />
                            <x-text-input id="notifications-limits-max_per_day" name="notifications[limits][max_per_day]"
                                type="number" class="mt-1 block w-full" :value="$v('notifications.limits.max_per_day')" />
                            <x-input-error :messages="$err('notifications.limits.max_per_day')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="search-max_results" :value="__('Maximum results per search')" />
                            <x-text-input id="search-max_results" name="search[max_results]"
                                type="number" class="mt-1 block w-full" :value="$v('search.max_results')" />
                            <x-input-error :messages="$err('search.max_results')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="search-timeout" :value="__('Search timeout (seconds)')" />
                            <x-text-input id="search-timeout" name="search[timeout]"
                                type="number" class="mt-1 block w-full" :value="$v('search.timeout')" />
                            <x-input-error :messages="$err('search.timeout')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="search-retries" :value="__('Search retries')" />
                            <x-text-input id="search-retries" name="search[retries]"
                                type="number" class="mt-1 block w-full" :value="$v('search.retries')" />
                            <x-input-error :messages="$err('search.retries')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monitoring-concurrency-max_global" :value="__('Max concurrent searches (global)')" />
                            <x-text-input id="monitoring-concurrency-max_global" name="monitoring[concurrency][max_global]"
                                type="number" class="mt-1 block w-full" :value="$v('monitoring.concurrency.max_global')" />
                            <x-input-error :messages="$err('monitoring.concurrency.max_global')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monitoring-concurrency-max_per_user" :value="__('Max concurrent searches (per user)')" />
                            <x-text-input id="monitoring-concurrency-max_per_user" name="monitoring[concurrency][max_per_user]"
                                type="number" class="mt-1 block w-full" :value="$v('monitoring.concurrency.max_per_user')" />
                            <x-input-error :messages="$err('monitoring.concurrency.max_per_user')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-900">{{ __('Execution frequency') }}</h3>

                    <div>
                        <x-input-label :value="__('Allowed search frequencies')" />
                        <div class="mt-2 flex flex-wrap gap-4">
                            @foreach (['hourly', 'daily', 'weekly', 'manual', 'custom'] as $frequency)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="monitoring[frequencies][]" value="{{ $frequency }}"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                        @checked(in_array($frequency, (array) $v('monitoring.frequencies'), true)) />
                                    {{ __(ucfirst($frequency)) }}
                                </label>
                            @endforeach
                        </div>
                        <x-input-error :messages="$err('monitoring.frequencies')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="notifications-digest-at" :value="__('Digest hour (0-23)')" />
                            <x-text-input id="notifications-digest-at" name="notifications[digest][at]"
                                type="number" class="mt-1 block w-full" :value="$v('notifications.digest.at')" />
                            <x-input-error :messages="$err('notifications.digest.at')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="notifications-digest-max_items" :value="__('Digest max items')" />
                            <x-text-input id="notifications-digest-max_items" name="notifications[digest][max_items]"
                                type="number" class="mt-1 block w-full" :value="$v('notifications.digest.max_items')" />
                            <x-input-error :messages="$err('notifications.digest.max_items')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monitoring-metrics-window_days" :value="__('Metrics window (days)')" />
                            <x-text-input id="monitoring-metrics-window_days" name="monitoring[metrics][window_days]"
                                type="number" class="mt-1 block w-full" :value="$v('monitoring.metrics.window_days')" />
                            <x-input-error :messages="$err('monitoring.metrics.window_days')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-900">{{ __('Estimated costs') }}</h3>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="monitoring-costs-per_result" :value="__('Cost per result')" />
                            <x-text-input id="monitoring-costs-per_result" name="monitoring[costs][per_result]"
                                type="number" step="0.000001" class="mt-1 block w-full" :value="$v('monitoring.costs.per_result')" />
                            <x-input-error :messages="$err('monitoring.costs.per_result')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="monitoring-costs-per_1k_tokens_ai" :value="__('Cost per 1K AI tokens')" />
                            <x-text-input id="monitoring-costs-per_1k_tokens_ai" name="monitoring[costs][per_1k_tokens_ai]"
                                type="number" step="0.000001" class="mt-1 block w-full" :value="$v('monitoring.costs.per_1k_tokens_ai')" />
                            <x-input-error :messages="$err('monitoring.costs.per_1k_tokens_ai')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                    <h3 class="font-semibold text-gray-900">{{ __('Retention policy') }}</h3>
                    <p class="text-sm text-gray-600">{{ __('Data older than these windows can be purged by the scheduled cleanup. Use 0 to keep data forever.') }}</p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div>
                            <x-input-label for="retention-topic_runs_days" :value="__('Keep search runs (days)')" />
                            <x-text-input id="retention-topic_runs_days" name="retention[topic_runs_days]"
                                type="number" class="mt-1 block w-full" :value="$v('retention.topic_runs_days')" />
                            <x-input-error :messages="$err('retention.topic_runs_days')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="retention-notification_deliveries_days" :value="__('Keep notifications (days)')" />
                            <x-text-input id="retention-notification_deliveries_days" name="retention[notification_deliveries_days]"
                                type="number" class="mt-1 block w-full" :value="$v('retention.notification_deliveries_days')" />
                            <x-input-error :messages="$err('retention.notification_deliveries_days')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="retention-news_entries_days" :value="__('Keep novedades (days)')" />
                            <x-text-input id="retention-news_entries_days" name="retention[news_entries_days]"
                                type="number" class="mt-1 block w-full" :value="$v('retention.news_entries_days')" />
                            <x-input-error :messages="$err('retention.news_entries_days')" class="mt-2" />
                        </div>
                    </div>
                </div>

                <div>
                    <x-primary-button>{{ __('Save settings') }}</x-primary-button>
                </div>
            </form>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('Restore defaults') }}</h3>
                <p class="text-sm text-gray-600">
                    {{ __('This removes every stored override. Each setting falls back to the value configured in the environment or its default.') }}
                </p>

                <form method="POST" action="{{ route('admin.settings.reset') }}"
                    onsubmit="return confirm('{{ __('Restore all settings to their default values?') }}');">
                    @csrf
                    <x-danger-button type="submit">{{ __('Restore defaults') }}</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
