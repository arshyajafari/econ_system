<?php

    namespace App\Http\Requests\Order;

    use App\Http\Requests\CrudRequest;

    class AllocateOrderItemRequest extends CrudRequest {
        public function rules(): array {
            return [
                'allocations' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'allocations.*.inventory_batch_id' => [
                    'required',
                    'string',
                    'exists:inventory_batches,public_id',
                ],
                'allocations.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
            ];
        }
    }
