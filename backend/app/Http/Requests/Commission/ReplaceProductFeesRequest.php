<?php

namespace App\Http\Requests\Commission;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ReplaceProductFeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'fees' => ['required', 'array'],
            'fees.*.product_id' => ['required', 'uuid'],
            'fees.*.fee_amount' => ['required', 'integer', 'min:0', 'max:9223372036854775807'],
        ];
    }
}
