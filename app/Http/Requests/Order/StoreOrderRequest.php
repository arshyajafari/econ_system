<?php

    namespace App\Http\Requests\Order;

    use App\Http\Requests\CrudRequest;

    class StoreOrderRequest extends CrudRequest {
        public function rules(): array {
            return [
                'customer_id' => [
                    'required',
                    'string',
                    'exists:customers,public_id',
                ],
                'sales_employee_id' => [
                    'required',
                    'string',
                    'exists:employees,public_id',
                ],
                'ordered_at' => [
                    'nullable',
                    'date',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],
                'items.*.product_id' => [
                    'required',
                    'string',
                    'exists:products,public_id',
                ],
                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'items.*.unit_price' => [
                    'required',
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
