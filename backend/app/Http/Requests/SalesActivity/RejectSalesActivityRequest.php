<?php

namespace App\Http\Requests\SalesActivity;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class RejectSalesActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::SPV;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:2000']];
    }
}
