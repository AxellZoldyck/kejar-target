<?php

namespace App\Http\Requests\Organization;

class UpdateTeamRequest extends StoreTeamRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'][0] = 'sometimes';

        return $rules;
    }
}
