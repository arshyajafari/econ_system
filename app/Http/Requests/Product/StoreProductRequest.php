<?php

    namespace App\Http\Requests\Api\Product;

    use App\Enums\ProductStatus;
    use Illuminate\Foundation\Http\FormRequest;
    use Illuminate\Validation\Rule;

    class StoreProductRequest extends FormRequest {
        public function authorize(): bool {
            return true;
        }

        public function rules(): array {
            return [
                'brand_id' => [
                    'required',
                    'string',
                    'exists:brands,public_id',
                ],
                'product_category_id' => [
                    'required',
                    'string',
                    'exists:product_categories,public_id',
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
                    Rule::enum(ProductStatus::class),
                ],
                'image' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'meta' => [
                    'nullable',
                    'array',
                ],
            ];
        }
    }
