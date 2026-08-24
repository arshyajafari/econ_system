<?php

    namespace App\Http\Requests\InventoryBatch;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreInventoryBatchRequest extends CrudRequest {
        public function rules(): array {
            return [
                'product_id' => [
                    'required',
                    'integer',
                    'exists:products,id',
                ],
                'batch_number' => [
                    'nullable',
                    'string',
                    'max:100',
                ],
                'expire_date' => [
                    'nullable',
                    'date',
                ],
                'received_at' => [
                    'nullable',
                    'date',
                ],
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'reserved_quantity' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
