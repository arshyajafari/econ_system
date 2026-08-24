<?php

    namespace App\Http\Requests\DoctorVisit;

    use App\Http\Requests\IndexRequest;

    class DoctorVisitIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'doctor_id' => [
                    'nullable',
                    'string',
                    'exists:doctors,public_id',
                ],
                'employee_id' => [
                    'nullable',
                    'string',
                    'exists:employees,public_id',
                ],
                'visit_from' => [
                    'nullable',
                    'date',
                ],
                'visit_to' => [
                    'nullable',
                    'date',
                    'after_or_equal:visit_from',
                ],
            ];
        }
    }
