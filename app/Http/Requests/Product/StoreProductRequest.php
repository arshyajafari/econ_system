<?php

    namespace App\Http\Requests\Product;

    use App\Enums\ProductStatus;
    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;
    use Illuminate\Validation\Rule;

    class StoreProductRequest extends CrudRequest {
        public function rules(): array {
            return [
                'brand_id' => [
                    'required',
                    'integer',
                    'exists:brands,id'
                ],
                'product_category_id' => [
                    'required',
                    'integer',
                    'exists:product_categories,id'
                ],
                'title' => [
                    'required',
                    'string',
                    'max:300'
                ],
                'barcode' => [
                    'nullable',
                    'string',
                    'max:50',
                    'unique:products,barcode'
                ],
                'sort_order' => [
                    'nullable',
                    'integer',
                    'min:0'
                ],
                'status' => [
                    'required',
                    Rule::enum(ProductStatus::class)
                ],
                'image' => [
                    'nullable',
                    'string',
                    'max:500'
                ],
                ...ValidationRules::description(),
                ...ValidationRules::meta(),
            ];
        }
    }
