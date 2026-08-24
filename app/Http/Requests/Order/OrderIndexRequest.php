<?php

    namespace App\Http\Requests\Order;

    use App\Enums\OrderStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class OrderIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'customer_id' => [
                    'nullable',
                    'string',
                    'exists:customers,public_id',
                ],
                'sales_employee_id' => [
                    'nullable',
                    'string',
                    'exists:employees,public_id',
                ],
                'status' => [
                    'nullable',
                    Rule::enum(OrderStatus::class),
                ],
                'ordered_from' => [
                    'nullable',
                    'date',
                ],
                'ordered_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:ordered_from',
                ],
            ];
        }
    }
