<?php

namespace App\Actions\Expense;

use App\Queries\Expense\ExpenseQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListExpensesAction
{
    public function execute(array $filters): LengthAwarePaginator
    {
        return ExpenseQuery::make()
            ->apply($filters)
            ->paginate($filters['per_page'] ?? 20);
    }
}
