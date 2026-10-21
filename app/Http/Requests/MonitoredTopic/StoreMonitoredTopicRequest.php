<?php

namespace App\Http\Requests\MonitoredTopic;

use App\Enums\NotificationMode;
use App\Enums\SearchFrequency;
use App\Models\MonitoredTopic;
use App\Services\Settings\SettingsRepository;
use App\Support\SettingKeys;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoredTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MonitoredTopic::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = app(SettingsRepository::class);
        $minMinutes = $settings->integer(SettingKeys::CUSTOM_INTERVAL_MIN);
        $maxMinutes = $settings->integer(SettingKeys::CUSTOM_INTERVAL_MAX);

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'language' => ['required', 'string', 'size:2'],
            'country' => ['nullable', 'string', 'size:2'],
            'frequency' => ['required', Rule::in($this->allowedFrequencies())],
            'custom_interval_minutes' => [
                'nullable',
                'integer',
                'required_if:frequency,custom',
                'min:'.$minMinutes,
                'max:'.$maxMinutes,
            ],
            'notify_email' => ['boolean'],
            'notify_push' => ['boolean'],
            'notification_mode' => ['nullable', Rule::in(NotificationMode::values())],
            'keywords_include' => ['nullable', 'string', 'max:5000'],
            'keywords_exclude' => ['nullable', 'string', 'max:5000'],
            'sources_preferred' => ['nullable', 'string', 'max:5000'],
            'sources_excluded' => ['nullable', 'string', 'max:5000'],
            'relevance' => ['nullable', 'array'],
            'relevance.min_relevance' => ['nullable', 'integer', 'min:0', 'max:100'],
            'relevance.notify_threshold' => ['nullable', 'integer', 'min:0', 'max:100'],
            'relevance.sort' => ['nullable', Rule::in(['date', 'relevance'])],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function includeKeywords(): array
    {
        return $this->splitLines($this->input('keywords_include'));
    }

    /**
     * @return array<int, string>
     */
    public function excludeKeywords(): array
    {
        return $this->splitLines($this->input('keywords_exclude'));
    }

    /**
     * @return array<int, string>
     */
    public function preferredSources(): array
    {
        return $this->splitLines($this->input('sources_preferred'));
    }

    /**
     * @return array<int, string>
     */
    public function excludedSources(): array
    {
        return $this->splitLines($this->input('sources_excluded'));
    }

    /**
     * @return array<int, string>
     */
    protected function splitLines(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/[\r\n,]+/', $value) ?: []),
            fn (string $item): bool => $item !== '',
        ));
    }

    /**
     * @return array<int, string>
     */
    protected function allowedFrequencies(): array
    {
        $allowed = app(SettingsRepository::class)->array(SettingKeys::FREQUENCIES);

        return array_values(array_intersect(
            SearchFrequency::values(),
            $allowed ?: SearchFrequency::values(),
        ));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $domains = array_merge($this->preferredSources(), $this->excludedSources());

            foreach ($domains as $domain) {
                if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain) !== 1) {
                    $validator->errors()->add('sources_preferred', __('Invalid domain: :domain', ['domain' => $domain]));
                    break;
                }
            }

            if (count($this->includeKeywords()) > 50) {
                $validator->errors()->add('keywords_include', __('You may add at most 50 keywords.'));
            }

            if (count($this->excludeKeywords()) > 50) {
                $validator->errors()->add('keywords_exclude', __('You may add at most 50 keywords.'));
            }
        });
    }
}
