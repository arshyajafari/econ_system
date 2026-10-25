<?php

namespace App\Http\Requests\Visit;

class StoreVisitWithSampleRequest extends StoreVisitRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'product_id' => ['required', 'string', 'exists:products,public_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'sample_description' => ['nullable', 'string'],
            'sample_client_operation_id' => ['nullable', 'string', 'max:64'],
        ];
    }
}
