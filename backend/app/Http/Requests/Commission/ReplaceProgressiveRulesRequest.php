<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceProgressiveRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'rules' => ['required', 'array'],
            'rules.*.product_id' => ['required', 'uuid'],
            'rules.*.sequence_number' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'rules.*.incentive_amount' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
        ];
    }
}
