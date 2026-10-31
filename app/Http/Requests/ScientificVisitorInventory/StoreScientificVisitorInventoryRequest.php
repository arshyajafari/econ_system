<?php

namespace App\Http\Requests\ScientificVisitorInventory;

use App\Http\Requests\CrudRequest;

class StoreScientificVisitorInventoryRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'string', 'exists:employees,public_id'],
            'inventory_batch_id' => ['required', 'string', 'exists:inventory_batches,public_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'received_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['nullable', 'string'],
        ];
    }
}