<?php

namespace App\Http\Requests\OrderReturn;

use App\Http\Requests\CrudRequest;

class UpdateOrderReturnRequest extends CrudRequest
{
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string'],
            'return_amount' => ['nullable', 'numeric', 'gt:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_item_id' => ['required', 'string', 'exists:order_items,public_id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.free_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.description' => ['nullable', 'string'],
        ];
    }
}
