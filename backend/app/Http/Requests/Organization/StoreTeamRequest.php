<?php

namespace App\Http\Requests\Organization;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('teams', 'name')->where(
                    fn ($query) => $query->where('company_id', $this->user()->company_id),
                ),
            ],
            'supervisor_id' => [
                'sometimes',
                'uuid',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('company_id', $this->user()->company_id)
                    ->where('role', UserRole::SPV->value)
                    ->where('is_active', true)),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
