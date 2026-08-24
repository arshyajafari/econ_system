<?php

    namespace App\Http\Requests\InventoryBatch;

    use App\Http\Requests\IndexRequest;

    class InventoryBatchIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'product_id' => [
                    'nullable',
                    'string',
                    'exists:products,public_id',
                ],
                'expired' => [
                    'nullable',
                    'boolean',
                ],
            ];
        }
    }
