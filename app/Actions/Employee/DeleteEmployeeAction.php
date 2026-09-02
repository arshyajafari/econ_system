<?php

    namespace App\Actions\Employee;

    use App\Exceptions\BusinessRuleException;
    use App\Models\Employee;
    use App\Models\Invoice;
    use App\Models\Order;
    use App\Models\Visit;
    use Illuminate\Support\Facades\DB;

    class DeleteEmployeeAction {
        public function execute(Employee $employee): void {
            DB::transaction(function () use ($employee) {
                $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);

                $hasOperationalHistory = $employee->user()->exists() || $employee->addresses()
                        ->exists() || $employee->locations()->exists() || $employee->customerAssignments()
                        ->exists() || $employee->deliveries()->exists() || Visit::query()
                        ->where('employee_id', $employee->id)->exists() || Order::query()
                        ->where('sales_employee_id', $employee->id)->exists() || Invoice::query()
                        ->where('employee_id', $employee->id)->exists();

                if ($hasOperationalHistory) {
                    throw new BusinessRuleException('این کارمند دارای سابقه عملیاتی است و امکان حذف آن وجود ندارد.');
                }

                $employee->delete();
            });
        }
    }
