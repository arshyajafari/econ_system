<?php

namespace App\Actions\Invoice;

use App\Queries\Invoice\InvoiceQuery;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListInvoicesAction
{
    public function execute(array $filters, ?User $user = null): LengthAwarePaginator
    {
        $user ??= auth()->user();
        return InvoiceQuery::make()->apply($filters, $user)->paginate($filters['per_page'] ?? 20);
    }
}
