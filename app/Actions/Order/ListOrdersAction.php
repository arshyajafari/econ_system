<?php

    namespace App\Actions\Order;

    use App\Queries\Order\OrderQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListOrdersAction {
        public function execute(array $filters): LengthAwarePaginator {
            return OrderQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
