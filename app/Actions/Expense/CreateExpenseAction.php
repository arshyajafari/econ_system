<?php

namespace App\Actions\Expense;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreateExpenseAction
{
    public function execute(array $data, User $user): Expense
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

        return Expense::query()->create([
            'title' => trim($data['title']),
            'amount' => $data['amount'],
            'category' => trim($data['category']),
            'expense_date' => $data['expense_date'],
            'employee_id' => $employeeId,
            'created_by' => $user->id,
            'description' => $data['description'] ?? null,
        ])->fresh(Expense::DEFAULT_RELATIONS);
    }
}
