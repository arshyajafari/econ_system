<?php

namespace App\Services;

use App\Enums\DeliveryStatus;
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
            ->whereHas('order.delivery', fn ($query) => $query->where('status', DeliveryStatus::DELIVERED))
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

        // Customer-level pending payments have no invoice_id, so they are
        // intentionally not included in Invoice::pendingPaidAmount().
        // They must nevertheless reduce the balance shown to the user;
        // otherwise a second pending payment can be created against the same debt.
        $pendingCustomerLevelPayments = (float) \App\Models\Payment::query()
            ->where('customer_id', $customerId)
            ->whereNull('invoice_id')
            ->where('status', PaymentStatus::PENDING)
            ->when(
                $excludePaymentId !== null,
                fn ($query) => $query->where('id', '!=', $excludePaymentId),
            )
            ->sum('amount');

        $pendingCustomerLevelDiscounts = (float) \App\Models\Payment::query()
            ->where('customer_id', $customerId)
            ->whereNull('invoice_id')
            ->where('status', PaymentStatus::PENDING)
            ->when(
                $excludePaymentId !== null,
                fn ($query) => $query->where('id', '!=', $excludePaymentId),
            )
            ->sum('settlement_discount_amount');

        $pendingCustomerLevelPayments += $pendingCustomerLevelDiscounts;

        // Only issued invoices with a confirmed delivery contribute to the
        // payable balance. Before delivery confirmation, the invoice is not
        // yet eligible for customer payment collection.
        // Return credit is already included in effectiveRemainingAmount().
        // Only payment-originated customer credit (such as an overpayment)
        // is subtracted separately because it is not attached to an invoice.
        $availablePaymentCredit = $this->customerCreditService->availablePaymentCreditAmount($customerId);

        return max(
            0.0,
            round(
                $invoiceBalance
                - (float) $pendingCustomerLevelPayments
                - $availablePaymentCredit,
                2,
            ),
        );
    }
}