<?php

    namespace App\Http\Requests\OrderReturn;

    use App\Http\Requests\CrudRequest;

    class StoreOrderReturnRequest extends CrudRequest {
        public function rules(): array {
            return [
                'order_id' => [
                    'required',
                    'string',
                    'exists:orders,public_id',
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
                'items.*.order_item_id' => [
                    'required',
                    'string',
                    'exists:order_items,public_id',
                    'distinct',
                ],
                'items.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'items.*.description' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
