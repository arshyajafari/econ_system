<?php

    namespace App\Actions\Employee;

    use App\Models\Employee;
    use Illuminate\Support\Facades\DB;

    class RestoreEmployeeAction {
        public function execute(Employee $employee): Employee {
            return DB::transaction(function () use ($employee) {
                $employee->restore();

                return $employee->fresh(Employee::DEFAULT_RELATIONS);
            });
        }
    }
