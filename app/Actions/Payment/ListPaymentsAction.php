<?php

    namespace App\Actions\Payment;

    use App\Queries\Payment\PaymentQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListPaymentsAction {
        public function execute(array $filters): LengthAwarePaginator {
            return PaymentQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
