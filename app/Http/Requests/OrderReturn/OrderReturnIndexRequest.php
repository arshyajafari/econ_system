<?php

    namespace App\Http\Requests\OrderReturn;

    use App\Enums\OrderReturnStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class OrderReturnIndexRequest extends IndexRequest {
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
                    Rule::enum(OrderReturnStatus::class),
                ],
                'completed_from' => [
                    'nullable',
                    'date',
                ],
                'completed_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:completed_from',
                ],
            ];
        }
    }
