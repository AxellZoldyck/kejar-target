<?php

namespace App\Http\Requests\Organization;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'sales_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('role', UserRole::SALES->value)),
            ],
            'type' => ['required', Rule::in(['weekly', 'monthly'])],
            'period_start' => ['required', 'date_format:Y-m-d'],
            'period_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_start'],
            'target_value' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
