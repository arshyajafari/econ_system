<?php

namespace App\Queries\Expense;

use App\Models\Expense;
use App\Queries\BaseQuery;

class ExpenseQuery extends BaseQuery
{
    protected function initialize(): void
    {
        $this->query = Expense::query()
            ->with(Expense::DEFAULT_RELATIONS);
    }

    public function apply(array $filters): static
    {
        $this->applySearch($filters['search'] ?? null, Expense::SEARCHABLE);

        if (!empty($filters['category'])) {
            $this->query->where('category', $filters['category']);
        }

        if (!empty($filters['employee_id'])) {
            $this->query->whereHas(
                'employee',
                fn ($query) => $query->where('public_id', $filters['employee_id'])
            );
        }

        if (!empty($filters['date_from'])) {
            $this->query->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $this->query->whereDate('expense_date', '<=', $filters['date_to']);
        }

        $sort = $filters['sort'] ?? null;
        if (($filters['direction'] ?? 'desc') === 'desc' && $sort && !str_starts_with($sort, '-')) {
            $sort = "-{$sort}";
        }

        $this->applySort(
            $sort,
            Expense::SORTABLE,
            'expense_date'
        );

        return $this;
    }
}
