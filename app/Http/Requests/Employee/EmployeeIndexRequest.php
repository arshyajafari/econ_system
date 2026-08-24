<?php

    namespace App\Http\Requests\Employee;

    use App\Enums\EmployeeStatus;
    use App\Enums\EmploymentType;
    use App\Enums\Gender;
    use Illuminate\Validation\Rule;
    use App\Http\Requests\IndexRequest;

    class EmployeeIndexRequest extends IndexRequest {
        public function rules(): array {
            return [
                ...$this->commonRules(),
                'status' => [
                    'nullable',
                    Rule::enum(EmployeeStatus::class),
                ],
                'gender' => [
                    'nullable',
                    Rule::enum(Gender::class),
                ],
                'employment_type' => [
                    'nullable',
                    Rule::enum(EmploymentType::class),
                ],
            ];
        }
    }
