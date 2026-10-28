<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Http\Requests\CrudRequest;
use App\Validation\ValidationRules;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends CrudRequest {
    public function rules(): array {
        return [
            'method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'settlement_discount_amount' => ['sometimes', 'numeric', 'gte:0'],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'cheque_issue_date' => ['sometimes', 'nullable', 'date'],
            'cheque_due_date' => ['sometimes', 'required_if:method,cheque', 'nullable', 'date', 'after_or_equal:cheque_issue_date'],
            'cheque_number' => ['sometimes', 'required_if:method,cheque', 'nullable', 'string', 'max:100'],
            'cheque_bank_name' => ['sometimes', 'required_if:method,cheque', 'nullable', 'string', 'max:150'],
            'cheque_sayad_id' => ['sometimes', 'nullable', 'regex:/^[0-9]{16}$/'],
            'payment_date' => ['sometimes', 'date'],
            'receipt_image' => [
                'sometimes',
                'nullable',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:3072',
            ],
            ...ValidationRules::description(),
        ];
    }
}
