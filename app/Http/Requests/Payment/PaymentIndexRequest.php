<?php

    namespace App\Http\Requests\Payment;

    use App\Enums\PaymentMethod;
    use App\Enums\PaymentStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class PaymentIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'invoice_id' => [
                    'nullable',
                    'string',
                    'exists:invoices,public_id',
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
                    Rule::enum(PaymentStatus::class),
                ],
                'method' => [
                    'nullable',
                    Rule::enum(PaymentMethod::class),
                ],
                'payment_from' => [
                    'nullable',
                    'date',
                ],
                'payment_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:payment_from',
                ],
            ];
        }
    }
