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

        // Scientific visitors are always scoped to their own employee inventory.
        // Keep depleted rows visible in the inventory table by applying the
        // available_only filter only when the caller explicitly requests it.
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
