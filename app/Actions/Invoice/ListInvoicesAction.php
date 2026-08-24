<?php

    namespace App\Actions\Invoice;

    use App\Queries\Invoice\InvoiceQuery;
    use Illuminate\Contracts\Pagination\LengthAwarePaginator;

    class ListInvoicesAction {
        public function execute(array $filters): LengthAwarePaginator {
            return InvoiceQuery::make()->apply($filters)->paginate($filters['per_page'] ?? 20);
        }
    }
