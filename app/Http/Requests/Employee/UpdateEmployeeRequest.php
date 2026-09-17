<?php

namespace App\Http\Requests\Employee;

use App\Enums\Role;
use App\Models\Employee;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest {
    public function rules(): array {
        $rules = parent::rules();
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;
        $userId = $employee instanceof Employee ? $employee->user?->id : null;

        $rules['national_code'] = ['required', 'string', 'max:25', Rule::unique('employees', 'national_code')->ignore($employeeId)];
        $rules['phone_number'] = ['required', 'string', 'max:20', Rule::unique('employees', 'phone_number')->ignore($employeeId)];
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)];
        $rules['login'] = ['nullable', 'string', 'min:3', 'max:100', Rule::unique('users', 'login')->ignore($userId)];
        $rules['password'] = ['nullable', 'string', 'min:8', 'max:255', 'confirmed'];
        $rules['roles'] = ['nullable', 'array', 'min:1'];
        $rules['roles.*'] = ['string', Rule::in(array_map(static fn(Role $role) => $role->value, Role::cases()))];

        return $rules;
    }
}
