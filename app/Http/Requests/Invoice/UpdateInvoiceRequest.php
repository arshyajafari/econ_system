<?php

    namespace App\Http\Requests\Invoice;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class UpdateInvoiceRequest extends CrudRequest {
        public function rules(): array {
            return [
                'due_date' => [
                    'sometimes',
                    'nullable',
                    'date',
                ],
                'discount_amount' => [
                    'sometimes',
                    'numeric',
                    'min:0',
                ],
                'tax_amount' => [
                    'sometimes',
                    'numeric',
                    'min:0',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
