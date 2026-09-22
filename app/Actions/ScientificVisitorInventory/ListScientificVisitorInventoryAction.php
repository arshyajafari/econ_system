<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Models\User;
use App\Queries\ScientificVisitorInventory\ScientificVisitorInventoryQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListScientificVisitorInventoryAction
{
    public function execute(array $filters, User $user): LengthAwarePaginator
    {
        $isScientificVisitor = $user->hasRole('scientific visitor');

        if ($isScientificVisitor) {
            // This is a server-side business rule, not a UI filter.
            $filters['available_only'] = true;
        }

        if ($isScientificVisitor && !$user->employee?->id) {
            return ScientificVisitorInventoryQuery::make()
                ->apply($filters, -1)
                ->paginate($filters['per_page'] ?? 50);
        }

        $employeeId = $isScientificVisitor ? $user->employee?->id : null;

        return ScientificVisitorInventoryQuery::make()
            ->apply($filters, $employeeId)
            ->paginate($filters['per_page'] ?? 50);
    }
}
