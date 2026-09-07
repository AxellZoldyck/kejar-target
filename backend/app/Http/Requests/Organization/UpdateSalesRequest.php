<?php

namespace App\Http\Requests\Organization;

use Illuminate\Validation\Rule;

class UpdateSalesRequest extends StoreSalesRequest
{
    public function rules(): array
    {
        $salesId = $this->route('sales');

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'email' => ['sometimes', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($salesId)],
            'password' => ['sometimes', 'string', 'min:8', 'max:255', 'confirmed'],
            'team_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('teams', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('is_active', true)),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
