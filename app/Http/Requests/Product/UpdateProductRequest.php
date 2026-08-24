<?php

    namespace App\Http\Requests\Product;

    use Illuminate\Validation\Rule;

    class UpdateProductRequest extends StoreProductRequest {
        public function rules(): array {
            $rules = parent::rules();

            $rules['barcode'] = [
                'nullable',
                'string',
                'max:50',
                Rule::unique('products', 'barcode')->ignore($this->route('product')),
            ];

            return $rules;
        }
    }
