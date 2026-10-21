<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SearchSettingRequest extends FormRequest
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
            'credentials' => ['nullable', 'array'],
            'credentials.brave.key' => ['nullable', 'string', 'max:500'],
            'credentials.tavily.key' => ['nullable', 'string', 'max:500'],
            'credentials.google_cse.key' => ['nullable', 'string', 'max:500'],
            'credentials.google_cse.cx' => ['nullable', 'string', 'max:500'],
            'credentials.openrouter.key' => ['nullable', 'string', 'max:500'],
        ];
    }
}
