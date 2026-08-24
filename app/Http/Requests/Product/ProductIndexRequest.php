<?php

    namespace App\Http\Requests\Product;

    use App\Http\Requests\IndexRequest;

    class ProductIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'brand_id' => [
                    'nullable',
                    'string',
                    'exists:brands,public_id',
                ],
                'product_category_id' => [
                    'nullable',
                    'string',
                    'exists:product_categories,public_id',
                ],
                'status' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
