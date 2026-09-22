<?php

namespace App\Http\Requests\ScientificVisitorInventory;

use App\Http\Requests\IndexRequest;

class ScientificVisitorInventoryIndexRequest extends IndexRequest
{
    protected function prepareForValidation(): void
    {
        $value = $this->input('available_only');

        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            if ($normalized === 'true') {
                $value = true;
            } elseif ($normalized === 'false') {
                $value = false;
            }
        }

        if ($value !== null) {
            $this->merge(['available_only' => $value]);
        }
    }

    public function rules(): array
    {
        return [
            ...$this->commonRules(),
            'employee_id' => ['nullable', 'string', 'exists:employees,public_id'],
            'product_id' => ['nullable', 'string', 'exists:products,public_id'],
            'available_only' => ['nullable', 'boolean'],
        ];
    }
}
