<?php

namespace App\Http\Requests\SalesActivity;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SALES;
    }

    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                'uuid',
                Rule::exists('products', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('is_active', true)),
            ],
            'activity_date' => ['required', 'date_format:Y-m-d'],
            'customer_reference' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'evidence' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
