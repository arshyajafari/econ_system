<?php

namespace App\Actions\Sample;

use App\Models\User;
use App\Queries\Sample\SampleQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSamplesAction
{
    public function execute(array $filters, User $user): LengthAwarePaginator
    {
        $employeeId = $user->hasRole('scientific visitor') ? $user->employee?->id : null;

        return SampleQuery::make()
            ->apply($filters, $employeeId)
            ->paginate($filters['per_page'] ?? 20);
    }
}