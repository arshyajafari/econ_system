<?php

    namespace App\Actions\Visit;

    use App\Queries\Visit\VisitQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListVisitsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return VisitQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
