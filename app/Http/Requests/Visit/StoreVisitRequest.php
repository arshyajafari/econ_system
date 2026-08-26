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
                'latitude' => [
                    'nullable',
                    'numeric',
                    'between:-90,90',
                ],
                'longitude' => [
                    'nullable',
                    'numeric',
                    'between:-180,180',
                ],
                'location_accuracy' => [
                    'nullable',
                    'numeric',
                    'min:0',
                    'max:100000',
                ],
                'location_captured_at' => [
                    'nullable',
                    'date',
                ],
                'client_operation_id' => [
                    'nullable',
                    'string',
                    'max:64',
                ],
                'purpose' => [
                    'nullable',
                    'string',
                    'max:150',
                ],
                ...ValidationRules::description(),
            ];
        }
    }
