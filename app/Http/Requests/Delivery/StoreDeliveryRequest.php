<?php

    namespace App\Http\Requests\Delivery;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreDeliveryRequest extends CrudRequest {
        public function rules(): array {
            return [
                'order_id' => [
                    'required',
                    'string',
                    'exists:orders,public_id',
                ],
                'recipient_name' => [
                    'required',
                    'string',
                    'max:150',
                ],
                'recipient_phone' => [
                    'required',
                    'string',
                    'max:30',
                ],
                'address' => [
                    'required',
                    'string',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
