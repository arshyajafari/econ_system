<?php

namespace App\Services;

use App\Enums\CustomerTransactionType;
use App\Exceptions\BusinessRuleException;
use App\Models\CustomerCreditAllocation;
use App\Models\CustomerTransaction;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class CustomerCreditService {
    public function availableAmount(int $customerId): float {
        $available = $this->creditSources($customerId)->sum('available');
        return max(0, round($available, 2));
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
        $remaining = $invoice->effectiveRemainingAmount();
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

        $alreadyAllocated = (float) CustomerCreditAllocation::query()
            ->where('source_transaction_id', $sourceTransaction->id)
            ->sum('amount');

        $sourceAvailable = max(0, round((float) $sourceTransaction->amount - $alreadyAllocated, 2));
        $invoiceRemaining = $invoice->effectiveRemainingAmount();
        $amount = min($requestedAmount, $sourceAvailable, $invoiceRemaining);

        return $this->createAllocation(
            sourceTransaction: $sourceTransaction,
            invoice: $invoice,
            amount: $amount,
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

        CustomerCreditAllocation::create([
            'customer_id' => $invoice->customer_id,
            'source_transaction_id' => $sourceTransaction->id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'allocated_at' => now(),
            'description' => $description ?? "استفاده از اعتبار مشتری برای فاکتور {$invoice->code}",
        ]);

        return $amount;
    }

    public function invoiceAppliedAmount(Invoice $invoice): float {
        return (float) CustomerCreditAllocation::query()
            ->where('invoice_id', $invoice->id)
            ->sum('amount');
    }

    protected function creditSources(int $customerId): Collection {
        $transactions = CustomerTransaction::query()
            ->with(['payment.invoice', 'creditAllocations'])
            ->where('customer_id', $customerId)
            ->where('type', CustomerTransactionType::CREDIT)
            ->where(function ($query) {
                $query->whereHas('orderReturn', fn ($return) => $return->where('status', \App\Enums\OrderReturnStatus::COMPLETED))
                    ->orWhereNotNull('payment_id');
            })
            ->orderBy('transaction_at')
            ->orderBy('id')
            ->get();

        $paymentGroups = $transactions
            ->filter(fn (CustomerTransaction $transaction) => $transaction->payment?->status?->value === 'confirmed')
            ->groupBy(fn (CustomerTransaction $transaction) => $transaction->payment?->invoice_id);

        $paymentAvailableByTransaction = [];

        foreach ($paymentGroups as $invoiceId => $group) {
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

            $available = $transaction->order_return_id
                ? max(0, round($sourceAmount - $allocated, 2))
                : (float) ($paymentAvailableByTransaction[$transaction->id] ?? 0);

            return [
                'transaction' => $transaction,
                'available' => $available,
            ];
        })->filter(fn (array $source) => $source['available'] > 0)->values();
    }
}
