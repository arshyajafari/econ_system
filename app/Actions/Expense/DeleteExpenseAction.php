<?php

namespace App\Actions\Expense;

use App\Models\Expense;

class DeleteExpenseAction
{
    public function execute(Expense $expense): void
    {
        $expense->delete();
    }
}
