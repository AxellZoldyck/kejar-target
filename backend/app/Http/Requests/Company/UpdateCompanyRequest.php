<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'activity_label' => ['sometimes', 'required', 'string', 'max:40'],
            'timezone' => ['sometimes', 'required', 'string', 'timezone'],
            'company_id' => ['prohibited'],
            'slug' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
