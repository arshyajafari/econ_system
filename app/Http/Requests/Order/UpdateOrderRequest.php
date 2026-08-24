<?php

    namespace App\Http\Requests\Order;

    use App\Http\Requests\CrudRequest;

    class UpdateOrderRequest extends CrudRequest {
        public function rules(): array {
            return [
                'customer_id' => [
                    'sometimes',
                    'string',
                    'exists:customers,public_id',
                ],
                'sales_employee_id' => [
                    'sometimes',
                    'string',
                    'exists:employees,public_id',
                ],
                'description' => [
                    'sometimes',
                    'nullable',
                    'string',
                ],
                'items' => [
                    'sometimes',
                    'array',
                    'min:1',
                ],
                'items.*.product_id' => [
                    'required_with:items',
                    'string',
                    'exists:products,public_id',
                ],
                'items.*.quantity' => [
                    'required_with:items',
                    'integer',
                    'min:1',
                ],
                'items.*.unit_price' => [
                    'required_with:items',
                    'numeric',
                    'min:0',
                ],
                'items.*.description' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
