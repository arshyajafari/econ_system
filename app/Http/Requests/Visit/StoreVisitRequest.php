<?php

    namespace App\Http\Requests\Visit;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class StoreVisitRequest extends CrudRequest {
        public function rules(): array {
            return [
                'doctor_id' => [
                    'required',
                    'string',
                    'exists:doctors,public_id',
                ],
                'visit_date' => [
                    'required',
                    'date',
                ],
                'purpose' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                ValidationRules::description(),
            ];
        }
    }
