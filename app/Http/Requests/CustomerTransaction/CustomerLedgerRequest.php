<?php

    namespace App\Http\Requests\CustomerTransaction;

    use App\Http\Requests\CrudRequest;

    class CustomerLedgerRequest extends CrudRequest {
        public function rules(): array {
            return [
                'from' => [
                    'nullable',
                    'date',
                ],
                'to' => [
                    'nullable',
                    'date',
                    'after_or_equal:from',
                ],
            ];
        }
    }
