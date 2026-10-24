<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Enums\PaymentStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\CustomerCreditAllocation;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;

class CustomerCreditService {
    public function availableAmount(int $customerId): float {
        return max(0, round($this->creditSources($customerId)->sum('available'), 2));
    }

    public function allocateToInvoice(int $customerId, Invoice $invoice, float $requestedAmount, ?string $description = null): float {
        $requestedAmount = round($requestedAmount, 2);

        if ($requestedAmount <= 0) {
            return 0.0;
        }

        if ((int) $invoice->customer_id !== $customerId) {
            throw new BusinessRuleException('فاکتور متعلق به این مشتری نیست.');
        }

        $available = $this->availableAmount($customerId);
        $remaining = $this->freshInvoice($invoice)->effectiveRemainingAmount();
        $amountToAllocate = min($requestedAmount, $available, $remaining);

        if ($amountToAllocate <= 0) {
            return 0.0;
        }

        $remainingToAllocate = $amountToAllocate;

        foreach ($this->creditSources($customerId) as $source) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $remainingToAllocate -= $this->createAllocation(
                sourceTransaction: $source['transaction'],
                invoice: $invoice,
                amount: min($source['available'], $remainingToAllocate),
                description: $description,
            );
        }

        return round($amountToAllocate - $remainingToAllocate, 2);
    }

    public function allocateSourceToInvoice(
        CustomerTransaction $sourceTransaction,
        Invoice $invoice,
        float $requestedAmount,
        ?string $description = null,
    ): float {
        $requestedAmount = round($requestedAmount, 2);

        if ($requestedAmount <= 0 || (int) $invoice->customer_id !== (int) $sourceTransaction->customer_id) {
            return 0.0;
        }

        return $this->createAllocation(
            sourceTransaction: $sourceTransaction,
            invoice: $invoice,
            amount: $requestedAmount,
            description: $description,
        );
    }

    protected function createAllocation(
        CustomerTransaction $sourceTransaction,
        Invoice $invoice,
        float $amount,
        ?string $description = null,
    ): float {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return 0.0;
        }

        // The source row is the mutex for all allocations from this credit.
        // Every allocation path must take this lock before reading its
        // allocated total, otherwise concurrent transactions can spend the
        // same credit twice.
        $lockedSource = CustomerTransaction::query()
            ->lockForUpdate()
            ->findOrFail($sourceTransaction->id);

        if ((int) $lockedSource->customer_id !== (int) $invoice->customer_id
            || $lockedSource->type !== CustomerTransactionType::CREDIT) {
            return 0.0;
        }

        $sourceAvailable = $this->availableAmountForLockedSource($lockedSource);
        $freshInvoice = $this->freshInvoice($invoice);
        $invoiceRemaining = $freshInvoice->effectiveRemainingAmount();
        $amount = min($amount, $sourceAvailable, $invoiceRemaining);

        if ($amount <= 0) {
            return 0.0;
        }

        CustomerCreditAllocation::create([
            'customer_id' => $freshInvoice->customer_id,
            'source_transaction_id' => $lockedSource->id,
            'invoice_id' => $freshInvoice->id,
            'amount' => round($amount, 2),
            'allocated_at' => now(),
            'description' => $description ?? "استفاده از اعتبار مشتری برای فاکتور {$freshInvoice->code}",
        ]);

        return round($amount, 2);
    }

    /**
     * Recalculate a source's spendable amount only after its row has been
     * locked. Allocation rows are read with a locking read so a transaction
     * that waited for the source lock sees allocations committed by the
     * previous holder rather than relying on a stale eager-loaded relation.
     */
    protected function availableAmountForLockedSource(CustomerTransaction $source): float
    {
        $allocated = (float) CustomerCreditAllocation::query()
            ->where('source_transaction_id', $source->id)
            ->lockForUpdate()
            ->get(['amount'])
            ->sum(fn (CustomerCreditAllocation $allocation) => (float) $allocation->amount);

        $sourceAmount = (float) $source->amount;

        if ($source->order_return_id !== null) {
            return max(0, round($sourceAmount - $allocated, 2));
        }

        if ($source->payment_id === null) {
            return 0.0;
        }

        $payment = Payment::query()->find($source->payment_id);

        if (!$payment || $payment->status !== PaymentStatus::CONFIRMED) {
            return 0.0;
        }

        // A customer-level payment is reusable credit in full. A payment
        // attached to an invoice contributes reusable credit only for the
        // portion that exceeds that invoice's total, matching creditSources().
        if ($payment->invoice_id === null) {
            return max(0, round($sourceAmount - $allocated, 2));
        }

        $invoiceTotal = (float) Invoice::query()
            ->whereKey($payment->invoice_id)
            ->value('total_amount');

        $transactions = CustomerTransaction::query()
            ->where('customer_id', $source->customer_id)
            ->where('type', CustomerTransactionType::CREDIT)
            ->whereHas('payment', fn ($query) => $query
                ->where('status', PaymentStatus::CONFIRMED)
                ->where('invoice_id', $payment->invoice_id))
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get(['id', 'amount']);

        $cumulative = 0.0;

        foreach ($transactions as $transaction) {
            $before = $cumulative;
            $cumulative += (float) $transaction->amount;

            if ((int) $transaction->id !== (int) $source->id) {
                continue;
            }

            $overpaymentBefore = max(0, round($before - $invoiceTotal, 2));
            $overpaymentAfter = max(0, round($cumulative - $invoiceTotal, 2));
            $availableForSource = max(0, round($overpaymentAfter - $overpaymentBefore, 2));

            return max(0, round($availableForSource - $allocated, 2));
        }

        return 0.0;
    }

    protected function freshInvoice(Invoice $invoice): Invoice
    {
        return Invoice::query()
            ->with(['payments', 'creditAllocations', 'returnTransactions.orderReturn'])
            ->findOrFail($invoice->id);
    }

    public function availablePaymentCreditAmount(int $customerId): float
    {
        return max(
            0,
            round(
                $this->creditSources($customerId)
                    ->filter(fn (array $source) => $source['transaction']->payment_id !== null)
                    ->sum('available'),
                2,
            ),
        );
    }

    /**
     * Return credits not already reflected in an originating invoice's
     * remaining amount. Unallocated return credit may reduce the originating
     * invoice virtually; only the excess is reusable against other invoices.
     */
    public function availableReturnCreditAmount(int $customerId): float
    {
        $availableReturnCredit = $this->creditSources($customerId)
            ->filter(fn (array $source) => $source['transaction']->order_return_id !== null)
            ->sum('available');

        $invoices = Invoice::query()
            ->with(['payments', 'creditAllocations', 'returnTransactions.orderReturn'])
            ->where('customer_id', $customerId)
            ->get();

        $virtuallyAppliedReturnCredit = $invoices->sum(
            fn (Invoice $invoice) => $invoice->unallocatedCompletedReturnCreditAmount(),
        );

        return max(0, round($availableReturnCredit - $virtuallyAppliedReturnCredit, 2));
    }

    /**
     * Apply reusable return credit to an invoice other than the return's
     * originating order. Credit already reflected virtually on the origin
     * invoice must not be allocated a second time.
     */
    public function allocateReturnCreditToInvoice(
        int $customerId,
        Invoice $invoice,
        float $requestedAmount,
        ?string $description = null,
    ): float {
        $requestedAmount = round($requestedAmount, 2);

        if ($requestedAmount <= 0 || (int) $invoice->customer_id !== $customerId) {
            return 0.0;
        }

        $remainingToAllocate = min(
            $requestedAmount,
            $this->availableReturnCreditAmount($customerId),
            $this->freshInvoice($invoice)->effectiveRemainingAmount(),
        );

        if ($remainingToAllocate <= 0) {
            return 0.0;
        }

        $allocatedTotal = 0.0;

        foreach ($this->creditSources($customerId)->filter(
            fn (array $source) => $source['transaction']->order_return_id !== null
                && (int) $source['transaction']->orderReturn?->order_id !== (int) $invoice->order_id
        ) as $source) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $allocated = $this->createAllocation(
                sourceTransaction: $source['transaction'],
                invoice: $invoice,
                amount: min($source['available'], $remainingToAllocate),
                description: $description,
            );

            $allocatedTotal += $allocated;
            $remainingToAllocate -= $allocated;
        }

        return round($allocatedTotal, 2);
    }

    public function allocatePaymentCreditToInvoice(
        int $customerId,
        Invoice $invoice,
        float $requestedAmount,
        ?string $description = null,
    ): float {
        $requestedAmount = round($requestedAmount, 2);

        if ($requestedAmount <= 0 || (int) $invoice->customer_id !== $customerId) {
            return 0.0;
        }

        $remainingToAllocate = min(
            $requestedAmount,
            $this->availablePaymentCreditAmount($customerId),
            $this->freshInvoice($invoice)->effectiveRemainingAmount(),
        );

        if ($remainingToAllocate <= 0) {
            return 0.0;
        }

        $allocatedTotal = 0.0;

        foreach ($this->creditSources($customerId)->filter(
            fn (array $source) => $source['transaction']->payment_id !== null
        ) as $source) {
            if ($remainingToAllocate <= 0) {
                break;
            }

            $allocated = $this->createAllocation(
                sourceTransaction: $source['transaction'],
                invoice: $invoice,
                amount: min($source['available'], $remainingToAllocate),
                description: $description,
            );

            $allocatedTotal += $allocated;
            $remainingToAllocate -= $allocated;
        }

        return round($allocatedTotal, 2);
    }

    public function invoiceAppliedAmount(Invoice $invoice): float {
        return (float) CustomerCreditAllocation::query()
            ->where('invoice_id', $invoice->id)
            ->sum('amount');
    }

    protected function creditSources(int $customerId): Collection {
        $transactions = CustomerTransaction::query()
            ->with(['payment.invoice', 'orderReturn', 'creditAllocations'])
            ->where('customer_id', $customerId)
            ->where('type', CustomerTransactionType::CREDIT)
            ->where(function ($query) {
                $query->whereHas('orderReturn', fn ($return) => $return->where('status', \App\Enums\OrderReturnStatus::COMPLETED))
                    ->orWhereNotNull('payment_id');
            })
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $paymentTransactions = $transactions
            ->filter(fn (CustomerTransaction $transaction) => $transaction->payment?->status?->value === 'confirmed');

        $paymentGroups = $paymentTransactions
            ->filter(fn (CustomerTransaction $transaction) => $transaction->payment?->invoice_id !== null)
            ->groupBy(fn (CustomerTransaction $transaction) => $transaction->payment->invoice_id);

        $paymentAvailableByTransaction = [];

        foreach ($paymentGroups as $group) {
            $invoice = $group->first()?->payment?->invoice;

            if (!$invoice) {
                continue;
            }

            $invoiceTotal = (float) $invoice->total_amount;
            $cumulative = 0.0;

            foreach ($group->sortBy(fn ($transaction) => [$transaction->transaction_at?->timestamp ?? 0, $transaction->id]) as $transaction) {
                $before = $cumulative;
                $cumulative += (float) $transaction->amount;
                $overpaymentBefore = max(0, round($before - $invoiceTotal, 2));
                $overpaymentAfter = max(0, round($cumulative - $invoiceTotal, 2));
                $availableForThisTransaction = max(0, round($overpaymentAfter - $overpaymentBefore, 2));
                $allocated = (float) $transaction->creditAllocations->sum('amount');
                $paymentAvailableByTransaction[$transaction->id] = max(
                    0,
                    round($availableForThisTransaction - $allocated, 2),
                );
            }
        }

        return $transactions->map(function (CustomerTransaction $transaction) use ($paymentAvailableByTransaction) {
            $allocated = (float) $transaction->creditAllocations->sum('amount');
            $sourceAmount = (float) $transaction->amount;
            $payment = $transaction->payment;

            if ($transaction->order_return_id) {
                $available = max(0, round($sourceAmount - $allocated, 2));
            } elseif ($payment?->invoice_id === null) {
                // New customer-level payments have no single invoice. Their
                // entire unallocated confirmed amount is reusable customer credit.
                $available = max(0, round($sourceAmount - $allocated, 2));
            } else {
                $available = (float) ($paymentAvailableByTransaction[$transaction->id] ?? 0);
            }

            return [
                'transaction' => $transaction,
                'available' => $available,
            ];
        })->filter(fn (array $source) => $source['available'] > 0)->values();
    }
}
