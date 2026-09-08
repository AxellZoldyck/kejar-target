<?php

namespace App\Http\Requests\Organization;

use Illuminate\Validation\Rule;

class UpdateTeamRequest extends StoreTeamRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = [
            'sometimes',
            'string',
            'max:120',
            Rule::unique('teams', 'name')
                ->where(fn ($query) => $query->where('company_id', $this->user()->company_id))
                ->ignore($this->route('team')),
        ];

        return $rules;
    }
}
