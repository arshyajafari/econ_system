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
            ->orderBy('issued_at')
            ->orderBy('id')
            ->get();

        $invoiceBalance = $invoices->sum(function (Invoice $invoice) use ($excludePaymentId): float {
            $remaining = $invoice->effectiveRemainingAmount();

            if ($excludePaymentId !== null) {
                $remaining -= (float) $invoice->payments
                    ->where('status', PaymentStatus::PENDING)
                    ->reject(fn ($payment) => (int) $payment->id === $excludePaymentId)
                    ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount);
            } else {
                $remaining -= $invoice->pendingPaidAmount();
            }

            return max(0.0, round($remaining, 2));
        });

        // The payable balance is customer-level debt: every issued invoice
        // participates in the balance calculation. Delivery state belongs to
        // fulfillment and must not make an existing invoice debt disappear.
        // Return credit is already included in effectiveRemainingAmount().
        // Only payment-originated customer credit (such as an overpayment)
        // is subtracted separately because it is not attached to an invoice.
        $availablePaymentCredit = $this->customerCreditService->availablePaymentCreditAmount($customerId);

        return max(0.0, round($invoiceBalance - $availablePaymentCredit, 2));
    }
}