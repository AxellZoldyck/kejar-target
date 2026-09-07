<?php

namespace App\Http\Requests\Organization;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('products', 'code')->where('company_id', $this->user()->company_id),
            ],
            'product_fee_amount' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
