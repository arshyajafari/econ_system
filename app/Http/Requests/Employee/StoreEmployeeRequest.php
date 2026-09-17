<?php

namespace App\Http\Requests\Employee;

use App\Enums\EmployeeActivityType;
use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\Role;
use App\Http\Requests\CrudRequest;
use App\Validation\ValidationRules;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends CrudRequest {
    protected function prepareForValidation(): void {
        if (!$this->filled('activities') && $this->filled('activity_type')) {
            $this->merge(['activities' => [$this->input('activity_type')]]);
        }
    }

    public function rules(): array {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_code' => ['required', 'string', 'max:25', 'unique:employees,national_code'],
            'phone_number' => ['required', 'string', 'max:20', 'unique:employees,phone_number'],
            'social_link' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:employees,email'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'birth_date' => ['nullable', 'date'],
            'card_number' => ['nullable', 'string', 'max:50'],
            'iban_number' => ['nullable', 'string', 'max:50'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'activities' => ['required', 'array', 'min:1'],
            'activities.*' => [Rule::enum(EmployeeActivityType::class)],
            'activity_type' => ['nullable', Rule::enum(EmployeeActivityType::class)],
            'hire_date' => ['required', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'login' => ['required', 'string', 'min:3', 'max:100', 'unique:users,login'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::in(array_map(static fn(Role $role) => $role->value, Role::cases()))],
            ...ValidationRules::address(),
            ...ValidationRules::meta(),
            ...ValidationRules::description(),
        ];
    }
}
