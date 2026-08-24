<?php

    namespace App\Http\Requests\ProductCategory;

    use App\Http\Requests\IndexRequest;

    class ProductCategoryIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'parent_id' => [
                    'nullable',
                    'string',
                    'exists:product_categories,public_id',
                ],
                'is_active' => [
                    'nullable',
                    'boolean',
                ],
            ];
        }
    }
