<?php

    namespace App\Http\Requests\Employee;

    use App\Enums\EmployeeStatus;
    use App\Enums\EmploymentType;
    use App\Enums\Gender;
    use App\Http\Requests\CrudRequest;
    use App\Validation\ValidationRules;
    use Illuminate\Validation\Rule;

    class StoreEmployeeRequest extends CrudRequest {
        public function rules(): array {
            return [
                'first_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'last_name' => [
                    'required',
                    'string',
                    'max:100',
                ],
                'national_code' => [
                    'required',
                    'string',
                    'max:25',
                    'unique:employees,national_code',
                ],
                'phone_number' => [
                    'required',
                    'string',
                    'max:20',
                    'unique:employees,phone_number',
                ],
                'social_number' => [
                    'nullable',
                    'string',
                    'max:20',
                ],
                'email' => [
                    'nullable',
                    'email',
                    'max:255',
                    'unique:employees,email',
                ],
                'gender' => [
                    'required',
                    Rule::enum(Gender::class),
                ],
                'birth_date' => [
                    'nullable',
                    'date',
                ],
                'card_number' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'iban_number' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'employment_type' => [
                    'required',
                    Rule::enum(EmploymentType::class),
                ],
                'hire_date' => [
                    'required',
                    'date',
                ],
                'termination_date' => [
                    'nullable',
                    'date',
                    'after_or_equal:hire_date',
                ],
                'status' => [
                    'required',
                    Rule::enum(EmployeeStatus::class),
                ],
                ...ValidationRules::address(),
                ...ValidationRules::meta(),
                ...ValidationRules::description()
            ];
        }
    }
