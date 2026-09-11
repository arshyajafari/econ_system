<?php

namespace App\Http\Requests\Employee;

use App\Models\Employee;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends StoreEmployeeRequest {
    public function rules(): array {
        $rules = parent::rules();
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : $employee;

        $rules['national_code'] = ['required', 'string', 'max:25', Rule::unique('employees', 'national_code')->ignore($employeeId)];
        $rules['phone_number'] = ['required', 'string', 'max:20', Rule::unique('employees', 'phone_number')->ignore($employeeId)];
        $rules['email'] = ['nullable', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employeeId)];

        return $rules;
    }
}
