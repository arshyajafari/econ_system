<?php

    namespace App\Actions\Employee;

    use App\Queries\Employee\EmployeeQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListEmployeesAction {
        public function execute(array $filters): LengthAwarePaginator {
            return EmployeeQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
