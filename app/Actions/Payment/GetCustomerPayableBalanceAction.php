<?php

namespace App\Actions\Payment;

use App\Models\Customer;
use App\Services\CustomerPayableBalanceService;

class GetCustomerPayableBalanceAction
{
    public function __construct(
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(Customer $customer): array
    {
        return [
            'customer' => [
                'id' => $customer->public_id,
                'code' => $customer->code,
                'name' => $customer->customer_name,
            ],
            'previous_balance' => $this->payableBalanceService->customerLevelBalance($customer->id),
            'payable_balance' => $this->payableBalanceService->calculate($customer->id),
        ];
    }
}