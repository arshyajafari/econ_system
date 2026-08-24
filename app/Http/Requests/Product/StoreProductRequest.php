<?php

    namespace App\Http\Requests\Product;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreProductRequest extends CrudRequest {
        public function rules(): array {
            return [
                'brand_id' => [
                    'required',
                    'integer',
                    'exists:brands,id',
                ],
                'product_category_id' => [
                    'required',
                    'integer',
                    'exists:product_categories,id',
                ],
                'title' => [
                    'required',
                    'string',
                    'max:300',
                ],
                'barcode' => [
                    'nullable',
                    'string',
                    'max:50',
                    'unique:products,barcode',
                ],
                'sort_order' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],
                'status' => [
                    'required',
                    'string',
                ],
                'image' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                ...ValidationRules::description(),
                ...ValidationRules::meta(),
            ];
        }
    }
