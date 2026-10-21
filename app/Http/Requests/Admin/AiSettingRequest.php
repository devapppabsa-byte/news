<?php

namespace App\Http\Requests\Admin;

use App\Enums\AISearchMode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => ['required', 'string', Rule::in(config('ai.providers', []))],
            'model' => ['required', 'string', 'max:150'],
            'search_mode' => ['required', Rule::in(AISearchMode::values())],
            'max_tokens' => ['required', 'integer', 'min:64', 'max:32000'],
            'timeout' => ['required', 'integer', 'min:5', 'max:300'],
            'fallback_provider' => ['nullable', 'string', Rule::in(config('ai.providers', []))],
            'fallback_model' => ['nullable', 'string', 'max:150'],
            'credentials' => ['nullable', 'array'],
            'credentials.openai.key' => ['nullable', 'string', 'max:500'],
            'credentials.anthropic.key' => ['nullable', 'string', 'max:500'],
            'credentials.openrouter.key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
