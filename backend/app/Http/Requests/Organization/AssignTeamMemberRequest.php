<?php

namespace App\Http\Requests\Organization;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTeamMemberRequest extends FormRequest
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
                    ->where('role', UserRole::SALES->value)
                    ->where('is_active', true)),
            ],
        ];
    }
}
