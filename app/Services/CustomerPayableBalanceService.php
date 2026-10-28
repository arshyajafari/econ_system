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
                    ->where('method', '!=', 'cheque')
                    ->reject(fn ($payment) => (int) $payment->id === $excludePaymentId)
                    ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount);
            } else {
                $remaining -= (float) $invoice->payments
                    ->where('status', PaymentStatus::PENDING)
                    ->where('method', '!=', 'cheque')
                    ->sum(fn ($payment) => (float) $payment->amount + (float) $payment->settlement_discount_amount);
            }

            return max(0.0, round($remaining, 2));
        });

        // Include all customer-level ledger entries that are not already represented
        // by an invoice, payment, or return. Restricting this to imported opening
        // balances hid manually recorded historical debt/credit from the payment form.
        $customerLevelBalance = $this->customerLevelBalance($customerId);

        // Customer-level pending payments have no invoice_id, so they are
        // intentionally not included in Invoice::pendingPaidAmount().
        // They must nevertheless reduce the balance shown to the user;
        // otherwise a second pending payment can be created against the same debt.
        $pendingCustomerLevelPayments = (float) \App\Models\Payment::query()
            ->where('customer_id', $customerId)
            ->whereNull('invoice_id')
            ->where('status', PaymentStatus::PENDING)
            ->where('method', '!=', 'cheque')
            ->when(
                $excludePaymentId !== null,
                fn ($query) => $query->where('id', '!=', $excludePaymentId),
            )
            ->sum('amount');

        $pendingCustomerLevelDiscounts = (float) \App\Models\Payment::query()
            ->where('customer_id', $customerId)
            ->whereNull('invoice_id')
            ->where('status', PaymentStatus::PENDING)
            ->where('method', '!=', 'cheque')
            ->when(
                $excludePaymentId !== null,
                fn ($query) => $query->where('id', '!=', $excludePaymentId),
            )
            ->sum('settlement_discount_amount');

        $pendingCustomerLevelPayments += $pendingCustomerLevelDiscounts;

        // Only issued invoices with a confirmed delivery contribute to the
        // payable balance. Before delivery confirmation, the invoice is not
        // yet eligible for customer payment collection.
        // Invoice remaining amounts already include return credit that can
        // be applied virtually to the originating invoice. Subtract only
        // customer credit that is still reusable after that virtual amount,
        // including excess return credit when the originating invoice was
        // already settled before the return.
        $availableCustomerCredit = $this->customerCreditService->availableAmount($customerId);
        $virtuallyAppliedReturnCredit = $invoices->sum(
            fn (Invoice $invoice) => $invoice->unallocatedCompletedReturnCreditAmount(),
        );
        $availableCustomerCredit = max(
            0.0,
            round($availableCustomerCredit - $virtuallyAppliedReturnCredit, 2),
        );

        return max(
            0.0,
            round(
                $invoiceBalance
                + (float) $customerLevelBalance
                - (float) $pendingCustomerLevelPayments
                - $availableCustomerCredit,
                2,
            ),
        );
    }

    /**
     * Net balance of standalone customer-ledger entries (debit minus credit).
     * Invoice-, payment-, and return-linked transactions are already reflected
     * in their source records and must not be counted again here.
     */
    public function customerLevelBalance(int $customerId): float
    {
        return (float) \App\Models\CustomerTransaction::query()
            ->where('customer_id', $customerId)
            ->whereNull('invoice_id')
            ->whereNull('payment_id')
            ->whereNull('order_return_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN type = ? THEN amount WHEN type = ? THEN -amount ELSE 0 END), 0) as balance", [
                \App\Enums\CustomerTransactionType::DEBIT->value,
                \App\Enums\CustomerTransactionType::CREDIT->value,
            ])
            ->value('balance');
    }
}