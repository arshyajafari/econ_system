<?php

namespace App\Actions\Sample;

use App\Models\User;
use App\Queries\Sample\SampleQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListSamplesAction
{
    public function execute(array $filters, User $user): LengthAwarePaginator
    {
        $isScientificVisitor = $user->hasRole('scientific visitor');
        $employeeId = $isScientificVisitor ? $user->employee?->id : null;

        $query = SampleQuery::make()->apply($filters, $employeeId);

        if (!$isScientificVisitor) {
            $query->excludeScientificVisitorSamples();
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
