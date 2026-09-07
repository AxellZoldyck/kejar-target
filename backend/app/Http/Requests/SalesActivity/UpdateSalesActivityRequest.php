<?php

namespace App\Http\Requests\SalesActivity;

class UpdateSalesActivityRequest extends StoreSalesActivityRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        foreach (['product_id', 'activity_date', 'customer_reference'] as $field) {
            $rules[$field][0] = 'sometimes';
        }
        $rules['notes'][0] = 'sometimes';
        $rules['evidence'][0] = 'sometimes';

        return $rules;
    }
}
