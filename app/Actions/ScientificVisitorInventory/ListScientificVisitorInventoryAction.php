<?php

namespace App\Actions\ScientificVisitorInventory;

use App\Models\User;
use App\Queries\ScientificVisitorInventory\ScientificVisitorInventoryQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListScientificVisitorInventoryAction
{
    public function execute(array $filters, User $user): LengthAwarePaginator
    {
        if ($user->hasRole('scientific visitor') && !$user->employee?->id) {
            return ScientificVisitorInventoryQuery::make()->apply($filters, -1)->paginate($filters['per_page'] ?? 50);
        }

        $employeeId = $user->hasRole('scientific visitor') ? $user->employee?->id : null;

        return ScientificVisitorInventoryQuery::make()
            ->apply($filters, $employeeId)
            ->paginate($filters['per_page'] ?? 50);
    }
}