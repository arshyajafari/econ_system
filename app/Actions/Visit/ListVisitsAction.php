<?php

namespace App\Actions\Visit;

use App\Models\User;
use App\Queries\Doctor\VisitQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListVisitsAction {
    public function execute(array $filters, ?User $user = null): LengthAwarePaginator {
        return VisitQuery::make()->apply($filters, $user)->paginate($filters['per_page'] ?? 20);
    }
}
