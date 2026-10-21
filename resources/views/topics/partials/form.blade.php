@php
    $topic = $topic ?? null;
    $keywords = $topic?->keywords ?? collect();
    $sources = $topic?->sources ?? collect();

    $includeKeywords = old('keywords.include', $keywords->where('type', \App\Enums\KeywordType::Include)->pluck('value')->all());
    $excludeKeywords = old('keywords.exclude', $keywords->where('type', \App\Enums\KeywordType::Exclude)->pluck('value')->all());
    $preferredSources = old('sources.preferred', $sources->where('type', \App\Enums\SourceType::Preferred)->pluck('domain')->all());
    $excludedSources = old('sources.excluded', $sources->where('type', \App\Enums\SourceType::Excluded)->pluck('domain')->all());

    $relevance = $topic?->relevance_preferences;

    $settings = app(\App\Services\Settings\SettingsRepository::class);
    $allowedFrequencies = $settings->array(\App\Support\SettingKeys::FREQUENCIES);
    $minInterval = $settings->integer(\App\Support\SettingKeys::CUSTOM_INTERVAL_MIN);
    $maxInterval = $settings->integer(\App\Support\SettingKeys::CUSTOM_INTERVAL_MAX);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="name" :value="__('Name')" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $topic?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Search instructions')" />
        <textarea id="description" name="description" rows="4"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $topic?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="language" :value="__('Language (ISO-639-1)')" />
            <x-text-input id="language" name="language" type="text" maxlength="2" class="mt-1 block w-full"
                :value="old('language', $topic?->language ?? 'es')" required />
            <x-input-error :messages="$errors->get('language')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="country" :value="__('Country (ISO-3166, optional)')" />
            <x-text-input id="country" name="country" type="text" maxlength="2" class="mt-1 block w-full"
                :value="old('country', $topic?->country)" />
            <x-input-error :messages="$errors->get('country')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="frequency" :value="__('Frequency')" />
            <select id="frequency" name="frequency"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                @foreach (\App\Enums\SearchFrequency::cases() as $frequency)
                    @if (in_array($frequency->value, $allowedFrequencies, true))
                        <option value="{{ $frequency->value }}" @selected(old('frequency', $topic?->frequency?->value) === $frequency->value)>
                            {{ ucfirst($frequency->value) }}
                        </option>
                    @endif
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('frequency')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="custom_interval_minutes" :value="__('Custom interval (minutes)')" />
            <x-text-input id="custom_interval_minutes" name="custom_interval_minutes" type="number"
                min="{{ $minInterval }}"
                max="{{ $maxInterval }}"
                class="mt-1 block w-full"
                :value="old('custom_interval_minutes', $topic?->custom_interval_minutes)" />
            <p class="mt-1 text-xs text-gray-500">
                {{ __('Between :min and :max minutes.', [
                    'min' => $minInterval,
                    'max' => $maxInterval,
                ]) }}
            </p>
            <x-input-error :messages="$errors->get('custom_interval_minutes')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="keywords_include" :value="__('Include keywords (one per line)')" />
            <textarea id="keywords_include" name="keywords_include" rows="4"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ implode("\n", $includeKeywords) }}</textarea>
            <x-input-error :messages="$errors->get('keywords_include')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="keywords_exclude" :value="__('Exclude keywords (one per line)')" />
            <textarea id="keywords_exclude" name="keywords_exclude" rows="4"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ implode("\n", $excludeKeywords) }}</textarea>
            <x-input-error :messages="$errors->get('keywords_exclude')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="sources_preferred" :value="__('Preferred domains (one per line)')" />
            <textarea id="sources_preferred" name="sources_preferred" rows="3"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ implode("\n", $preferredSources) }}</textarea>
            <x-input-error :messages="$errors->get('sources_preferred')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="sources_excluded" :value="__('Excluded domains (one per line)')" />
            <textarea id="sources_excluded" name="sources_excluded" rows="3"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ implode("\n", $excludedSources) }}</textarea>
            <x-input-error :messages="$errors->get('sources_excluded')" class="mt-2" />
        </div>
    </div>

    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <input type="checkbox" id="notify_email" name="notify_email" value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('notify_email', $topic?->notify_email ?? true)) />
            <x-input-label for="notify_email" :value="__('Email notifications')" />
        </div>
        <div class="flex items-center gap-2">
            <input type="checkbox" id="notify_push" name="notify_push" value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                @checked(old('notify_push', $topic?->notify_push ?? false)) />
            <x-input-label for="notify_push" :value="__('Push notifications')" />
        </div>
    </div>

    <div>
        <x-input-label for="notification_mode" :value="__('Email notification mode')" />
        <select id="notification_mode" name="notification_mode"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">— {{ __('Use my global preference') }} —</option>
            @foreach (\App\Enums\NotificationMode::cases() as $mode)
                <option value="{{ $mode->value }}" @selected(old('notification_mode', $topic?->notification_mode?->value) === $mode->value)>
                    {{ __($mode->label()) }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('notification_mode')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
            <x-input-label for="relevance_min" :value="__('Minimum relevance')" />
            <x-text-input id="relevance_min" name="relevance[min_relevance]" type="number" min="0" max="100"
                class="mt-1 block w-full"
                :value="old('relevance.min_relevance', $relevance?->minRelevance ?? config('monitoring.relevance.min_relevance'))" />
        </div>
        <div>
            <x-input-label for="relevance_threshold" :value="__('Notify threshold')" />
            <x-text-input id="relevance_threshold" name="relevance[notify_threshold]" type="number" min="0" max="100"
                class="mt-1 block w-full"
                :value="old('relevance.notify_threshold', $relevance?->notifyThreshold ?? config('monitoring.relevance.notify_threshold'))" />
        </div>
        <div>
            <x-input-label for="relevance_sort" :value="__('Sort results by')" />
            <select id="relevance_sort" name="relevance[sort]"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="date" @selected(old('relevance.sort', $relevance?->sort) === 'date')>{{ __('Date') }}</option>
                <option value="relevance" @selected(old('relevance.sort', $relevance?->sort) === 'relevance')>{{ __('Relevance') }}</option>
            </select>
        </div>
    </div>
</div>
