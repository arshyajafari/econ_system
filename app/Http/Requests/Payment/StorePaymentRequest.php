<?php

namespace App\Http\Requests\Payment;

use App\Enums\PaymentMethod;
use App\Http\Requests\CrudRequest;
use App\Validation\ValidationRules;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends CrudRequest {
    public function rules(): array {
        return [
            'invoice_id' => ['required', 'string', 'exists:invoices,public_id'],
            'method' => ['required', Rule::enum(PaymentMethod::class)],
            'amount' => ['required', 'numeric', 'gt:0'],
            'settlement_discount_amount' => ['nullable', 'numeric', 'gte:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'receipt_image' => [
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
