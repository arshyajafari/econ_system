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
