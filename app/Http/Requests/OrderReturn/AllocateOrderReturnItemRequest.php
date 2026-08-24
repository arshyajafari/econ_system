<?php

    namespace App\Http\Requests\OrderReturn;

    use App\Http\Requests\CrudRequest;

    class AllocateOrderReturnItemRequest extends CrudRequest {
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
                    'distinct',
                ],
                'allocations.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
            ];
        }
    }
