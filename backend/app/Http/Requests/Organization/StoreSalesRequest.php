<?php

namespace App\Http\Requests\Organization;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'team_id' => [
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
