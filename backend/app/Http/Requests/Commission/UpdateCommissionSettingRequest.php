<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCommissionSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'multiplier_enabled' => ['required', 'boolean'],
            'progressive_enabled' => ['required', 'boolean'],
            'progressive_overflow_behavior' => ['required', Rule::in(['zero', 'repeat_last'])],
        ];
    }
}
