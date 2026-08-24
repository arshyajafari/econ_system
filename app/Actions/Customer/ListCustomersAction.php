<?php

    namespace App\Actions\Customer;

    use App\Queries\Customer\CustomerQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListCustomersAction {
        public function execute(array $filters): LengthAwarePaginator {
            return CustomerQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
