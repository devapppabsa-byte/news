<?php

namespace App\Http\Requests\Admin;

use App\Support\SettingKeys;
use Illuminate\Foundation\Http\FormRequest;

class MailSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return SettingKeys::mailRules();
    }
}
