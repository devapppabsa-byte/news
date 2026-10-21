<?php

namespace App\Http\Requests\Admin;

use App\Support\SettingKeys;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
        return SettingKeys::rules();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = (int) $this->input(SettingKeys::CUSTOM_INTERVAL_MIN);
            $max = (int) $this->input(SettingKeys::CUSTOM_INTERVAL_MAX);

            if ($min > 0 && $max > 0 && $min > $max) {
                $validator->errors()->add(
                    SettingKeys::CUSTOM_INTERVAL_MAX,
                    __('The maximum interval must be greater than or equal to the minimum interval.'),
                );
            }
        });
    }
}
