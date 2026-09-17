<?php

namespace App\Http\Requests\Invoice;

use App\Enums\InvoiceStatus;
use App\Http\Requests\IndexRequest;
use Illuminate\Validation\Rule;

class InvoiceIndexRequest extends IndexRequest {
    protected function prepareForValidation(): void {
        if (!$this->has('settled')) {
            return;
        }

        $value = $this->input('settled');

        if ($value === 'true' || $value === '1') {
            $this->merge(['settled' => true]);
        } elseif ($value === 'false' || $value === '0') {
            $this->merge(['settled' => false]);
        }
    }

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
                Rule::enum(InvoiceStatus::class),
            ],
            'settled' => [
                'nullable',
                'boolean',
            ],
            'issued_from' => [
                'nullable',
                'date',
            ],
            'issued_to' => [
                'nullable',
                'date',
                'after_or_equal:issued_from',
            ],
        ];
    }
}
