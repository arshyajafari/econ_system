<?php

    namespace App\Http\Requests\Sample;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class UpdateSampleRequest extends CrudRequest {
        public function rules(): array {
            return [
                'quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
