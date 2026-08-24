<?php

    namespace App\Actions\Employee;

    use App\Models\Employee;
    use Illuminate\Support\Facades\DB;

    class DeleteEmployeeAction {
        public function execute(Employee $employee): void {
            DB::transaction(function () use ($employee) {

                // بعداً بررسی می‌کنیم:
                //
                // اگر User فعال دارد
                // اگر سفارش ثبت کرده
                // اگر ویزیت ثبت کرده
                // اگر فاکتور ثبت کرده
                //
                // اجازه حذف ندهیم.

                $employee->delete();
            });
        }
    }
