<?php

namespace App\Actions\Expense;

use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateExpenseAction
{
    public function execute(Expense $expense, array $data): Expense
    {
        $employeeId = null;

        if (!empty($data['employee_id'])) {
            $employeeId = Employee::query()
                ->where('public_id', $data['employee_id'])
                ->value('id');

            if (!$employeeId) {
                throw (new ModelNotFoundException())->setModel(Employee::class);
            }
        }

        $expense->update([
            'title' => trim($data['title']),
            'amount' => $data['amount'],
            'category' => trim($data['category']),
            'expense_date' => $data['expense_date'],
            'employee_id' => $employeeId,
            'description' => $data['description'] ?? null,
        ]);

        return $expense->fresh(Expense::DEFAULT_RELATIONS);
    }
}
