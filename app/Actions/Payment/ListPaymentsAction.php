<?php

namespace App\Actions\Payment;

use App\Models\User;
use App\Queries\Payment\PaymentQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPaymentsAction
{
    public function execute(array $filters, ?User $user = null): LengthAwarePaginator
    {
        $user ??= auth()->user();
        return PaymentQuery::make()->apply($filters, $user)->paginate($filters['per_page'] ?? 20);
    }
}
