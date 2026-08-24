<?php

    namespace App\Http\Requests\ProductCategory;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreProductCategoryRequest extends CrudRequest {
        public function rules(): array {
            return [
                'title' => [
                    'required',
                    'string',
                    'max:150',
                ],
                'parent_id' => [
                    'nullable',
                    'integer',
                    'exists:product_categories,id',
                ],
                'sort_order' => [
                    'nullable',
                    'integer',
                    'min:0',
                ],
                'is_active' => [
                    'nullable',
                    'boolean',
                ],
                ...ValidationRules::description(),
                ...ValidationRules::meta(),
            ];
        }
    }
