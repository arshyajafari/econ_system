<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;

class CustomerPayableBalanceService
{
    public function __construct(
        protected CustomerCreditService $customerCreditService,
    ) {
    }

    public function calculate(int $customerId, ?int $excludePaymentId = null): float
    {
        $invoices = Invoice::query()
            ->with(['payments', 'creditAllocations', 'returnTransactions.orderReturn'])
            ->where('customer_id', $customerId)
            ->where('status', InvoiceStatus::ISSUED)
            ->whereHas('order.delivery', fn ($query) => $query->whereIn('status', ['shipped', 'delivered']))
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();

        $invoiceBalance = $invoices->sum(function (Invoice $invoice) use ($excludePaymentId): float {
            $confirmed = $invoice->confirmedPaidAmount();
            $pending = $invoice->pendingPaidAmount();

            if ($excludePaymentId !== null) {
                $pending = (float) $invoice->payments
                    ->where('status', PaymentStatus::PENDING)
                    ->reject(fn ($payment) => (int) $payment->id === $excludePaymentId)
                    ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount);
            }

            $appliedCredit = $invoice->appliedCustomerCreditAmount();

            return max(
                0.0,
                round(
                    (float) $invoice->total_amount
                    - $confirmed
                    - $pending
                    - $appliedCredit,
                    2,
                ),
            );
        });

        $availableCredit = $this->customerCreditService->availableAmount($customerId);

        return max(0.0, round($invoiceBalance - $availableCredit, 2));
    }
}