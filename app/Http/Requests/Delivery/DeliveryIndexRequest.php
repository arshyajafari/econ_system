<?php

    namespace App\Http\Requests\Delivery;

    use App\Enums\DeliveryStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class DeliveryIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'order_id' => [
                    'nullable',
                    'string',
                    'exists:orders,public_id',
                ],
                'customer_id' => [
                    'nullable',
                    'string',
                    'exists:customers,public_id',
                ],
                'employee_id' => [
                    'nullable',
                    'string',
                    'exists:employees,public_id',
                ],
                'status' => [
                    'nullable',
                    Rule::enum(DeliveryStatus::class),
                ],
            ];
        }
    }
