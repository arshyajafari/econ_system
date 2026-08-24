<?php

    namespace App\Http\Requests\Inventory;

    use App\Http\Requests\IndexRequest;

    class ReceiveInventoryRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'product_id' => [
                    'required',
                    'string',
                    'exists:products,public_id',
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
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'received_at' => [
                    'nullable',
                    'date',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
