<?php

namespace App\Http\Requests\Organization;

class UpdateTargetRequest extends StoreTargetRequest
{
    public function rules(): array
    {
        return [
            'target_value' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
