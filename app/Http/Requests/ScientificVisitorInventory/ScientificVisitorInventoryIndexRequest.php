<?php

namespace App\Http\Requests\ScientificVisitorInventory;

use App\Http\Requests\IndexRequest;

class ScientificVisitorInventoryIndexRequest extends IndexRequest
{
    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            'product_id' => ['nullable', 'string', 'exists:products,public_id'],
            'available_only' => ['nullable', 'boolean'],

    public function prepareForValidation(): void
    {
        if ($this->has('available_only')) {
            $value = $this->input('available_only');
            $this->merge(['available_only' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
        }
    }
        ];
    }
}