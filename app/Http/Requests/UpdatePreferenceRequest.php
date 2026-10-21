<?php

namespace App\Http\Requests;

use App\Enums\NotificationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'size:2'],
            'timezone' => ['required', 'string', 'timezone'],
            'email_notifications' => ['boolean'],
            'notification_mode' => ['required', Rule::in(NotificationMode::values())],
            'push_notifications' => ['boolean'],
            'push_private' => ['boolean'],
            'digest_frequency' => ['required', Rule::in(['daily', 'weekly'])],
        ];
    }
}
