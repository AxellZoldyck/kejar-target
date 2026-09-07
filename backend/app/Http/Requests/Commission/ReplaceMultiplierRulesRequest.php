<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceMultiplierRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.min_sa' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'rules.*.max_sa' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'rules.*.multiplier_value' => ['required', 'regex:/^\d{1,5}(?:\.\d{1,4})?$/'],
        ];
    }
}
