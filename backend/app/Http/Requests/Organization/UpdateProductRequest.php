<?php

namespace App\Http\Requests\Organization;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:160'],
            'code' => [
                'sometimes',
                'string',
                'max:60',
                Rule::unique('products', 'code')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($productId),
            ],
            'product_fee_amount' => ['sometimes', 'integer', 'min:0', 'max:9223372036854775807'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
