<?php

namespace App\Http\Requests\ScientificVisitorInventory;

use App\Http\Requests\CrudRequest;

class UpdateScientificVisitorInventoryRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'inventory_batch_id' => ['nullable', 'string', 'exists:inventory_batches,public_id'],
            'description' => ['nullable', 'string'],
        ];
    }
}
