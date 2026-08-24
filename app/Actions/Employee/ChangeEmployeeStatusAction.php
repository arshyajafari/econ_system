<?php

    namespace App\Actions\Employee;

    use App\Enums\EmployeeStatus;
    use App\Models\Employee;
    use Illuminate\Support\Facades\DB;

    class ChangeEmployeeStatusAction {
        public function execute(Employee $employee, EmployeeStatus $status): Employee {

            return DB::transaction(function () use (
                $employee, $status,
            ) {

                if ($employee->status === $status) {
                    return $employee;
                }

                $employee->update([
                    'status' => $status,
                ]);

                return $employee->fresh(Employee::DEFAULT_RELATIONS);
            });
        }
    }
