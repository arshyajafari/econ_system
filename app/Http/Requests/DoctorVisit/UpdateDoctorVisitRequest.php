<?php

    namespace App\Http\Requests\DoctorVisit;

    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;

    class UpdateDoctorVisitRequest extends CrudRequest {
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
                ...ValidationRules::description(),
                'samples' => [
                    'nullable',
                    'array',
                ],
                'samples.*.product_id' => [
                    'required',
                    'string',
                    'exists:products,public_id',
                    'distinct',
                ],
                'samples.*.quantity' => [
                    'required',
                    'integer',
                    'min:1',
                ],
                'samples.*.description' => [
                    'nullable',
                    'string',
                ],
            ];
        }
    }
