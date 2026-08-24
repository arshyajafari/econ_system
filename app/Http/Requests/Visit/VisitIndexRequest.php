<?php

    namespace App\Http\Requests\Visit;

    use App\Enums\VisitStatus;
    use App\Http\Requests\IndexRequest;
    use Illuminate\Validation\Rule;

    class VisitIndexRequest extends IndexRequest {
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
                'status' => [
                    'nullable',
                    Rule::enum(VisitStatus::class),
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
