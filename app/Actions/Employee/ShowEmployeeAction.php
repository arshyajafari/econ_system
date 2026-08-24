<?php

    namespace App\Actions\Employee;

    use App\Models\Employee;

    class ShowEmployeeAction {
        public function execute(Employee $employee): Employee {
            return $employee->loadMissing(Employee::DEFAULT_RELATIONS);
        }
    }
