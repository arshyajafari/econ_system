<?php

namespace App\Actions\Payment;

use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\CustomerPayableBalanceService;

class GetCustomerPayableBalanceAction
{
    public function __construct(
        protected CustomerPayableBalanceService $payableBalanceService,
    ) {
    }

    public function execute(Customer $customer, ?string $excludePaymentPublicId = null): array
    {
        $excludePayment = $excludePaymentPublicId
            ? Payment::query()
                ->where('public_id', $excludePaymentPublicId)
                ->where('customer_id', $customer->id)
                ->where('status', PaymentStatus::PENDING)
                ->first()
            : null;

        return [
            'customer' => [
                'id' => $customer->public_id,
                'code' => $customer->code,
                'name' => $customer->customer_name,
            ],
            'previous_balance' => $this->payableBalanceService->customerLevelBalance($customer->id),
            'payable_balance' => $this->payableBalanceService->calculate(
                $customer->id,
                $excludePayment?->id,
            ),
        ];
    }
}