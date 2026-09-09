<?php

    namespace App\Http\Requests\Api\Product;

    use Illuminate\Validation\Rule;

    class UpdateProductRequest extends StoreProductRequest {
        public function rules(): array {
            return array_merge(parent::rules(), [
                'barcode' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('products', 'barcode')->ignore($this->route('product')),
                ],
            ]);
        }
    }
