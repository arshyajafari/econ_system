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
        $isAdmin = $user->hasRole('admin');
        $employeeId = $isScientificVisitor ? $user->employee?->id : null;

        $query = SampleQuery::make()->apply($filters, $employeeId);

        // Scientific visitors see only their own samples. Admins need access
        // to all samples for operational oversight; other roles continue to
        // see only non-scientific-visitor samples.
        if (!$isScientificVisitor && !$isAdmin) {
            $query->excludeScientificVisitorSamples();
        }

        return $query->paginate($filters['per_page'] ?? 20);
    }
}
