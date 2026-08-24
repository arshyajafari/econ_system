<?php

    namespace App\Http\Requests\Employee;

    use App\Enums\EmployeeStatus;
    use App\Http\Requests\BaseFormRequest;
    use Illuminate\Validation\Rule;

    class ChangeEmployeeStatusRequest extends BaseFormRequest {
        public function rules(): array {
            return [
                'status' => [
                    'required',
                    Rule::enum(EmployeeStatus::class),
                ],
            ];
        }

        public function status(): EmployeeStatus {
            return EmployeeStatus::from($this->validated('status'));
        }
    }
