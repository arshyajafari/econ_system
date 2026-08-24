<?php

    namespace App\Http\Requests\Sample;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreSampleRequest extends CrudRequest {
        public function rules(): array {
            return [
                'visit_id' => [
                    'required',
                    'string',
                    'exists:visits,public_id',
                ],
                'product_id' => [
                    'required',
                    'string',
                    'exists:products,public_id',
                ],
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
